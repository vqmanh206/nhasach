<?php
declare(strict_types=1);

final class Sale extends Model
{
    public function all(string $search = ''): array
    {
        $sql = 'SELECT s.*, c.name AS customer_name, u.name AS user_name
                FROM sales s
                LEFT JOIN customers c ON c.id = s.customer_id
                LEFT JOIN users u ON u.id = s.user_id';
        $params = [];
        if ($search !== '') {
            $sql .= ' WHERE c.name LIKE :search OR u.name LIKE :search OR s.status LIKE :search';
            $params['search'] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY s.created_at DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sale = $this->db->prepare(
            'SELECT s.*, c.name AS customer_name, u.name AS user_name
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE s.id = ? LIMIT 1'
        );
        $sale->execute([$id]);
        $result = $sale->fetch();
        if (!$result) {
            return null;
        }

        $items = $this->db->prepare(
            'SELECT si.*, b.title AS book_title, b.price AS book_price
             FROM sale_items si
             LEFT JOIN books b ON b.id = si.book_id
             WHERE si.sale_id = ?
             ORDER BY si.id ASC'
        );
        $items->execute([$id]);
        $result['items'] = $items->fetchAll();
        return $result;
    }

    public function customers(): array
    {
        return $this->db->query('SELECT id, name FROM customers ORDER BY name ASC')->fetchAll();
    }

    public function books(): array
    {
        return $this->db->query('SELECT id, title, price, stock_quantity FROM books WHERE is_active = 1 ORDER BY title ASC')->fetchAll();
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $sale = $this->db->prepare(
                'INSERT INTO sales (customer_id, user_id, total_amount, status)
                 VALUES (:customer_id, :user_id, :total_amount, :status)'
            );
            $sale->execute([
                'customer_id' => $data['customer_id'],
                'user_id' => $data['user_id'],
                'total_amount' => $data['total_amount'],
                'status' => $data['status'],
            ]);
            $saleId = (int) $this->db->lastInsertId();

            foreach ($data['items'] as $item) {
                $bookId = (int) ($item['book_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);
                if ($bookId <= 0 || $quantity <= 0) {
                    throw new InvalidArgumentException('Chi tiết hóa đơn không hợp lệ.');
                }

                $price = (float) ($item['unit_price'] ?? 0);
                $insertItem = $this->db->prepare(
                    'INSERT INTO sale_items (sale_id, book_id, quantity, unit_price)
                     VALUES (:sale_id, :book_id, :quantity, :unit_price)'
                );
                $insertItem->execute([
                    'sale_id' => $saleId,
                    'book_id' => $bookId,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                ]);

                $stockUpdate = $this->db->prepare(
                    'UPDATE books SET stock_quantity = stock_quantity - :quantity WHERE id = :book_id AND stock_quantity >= :quantity'
                );
                $stockUpdate->execute([
                    'quantity' => $quantity,
                    'book_id' => $bookId,
                ]);

                if ($stockUpdate->rowCount() !== 1) {
                    throw new RuntimeException('Sách không đủ tồn kho để bán.');
                }
            }

            $this->db->commit();
            return $saleId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function delete(int $id): bool
    {
        $this->db->beginTransaction();
        try {
            $items = $this->db->prepare('SELECT book_id, quantity FROM sale_items WHERE sale_id = ?');
            $items->execute([$id]);
            $itemRows = $items->fetchAll();

            foreach ($itemRows as $item) {
                $stockRestore = $this->db->prepare(
                    'UPDATE books SET stock_quantity = stock_quantity + :quantity WHERE id = :book_id'
                );
                $stockRestore->execute([
                    'quantity' => (int) $item['quantity'],
                    'book_id' => (int) $item['book_id'],
                ]);
            }

            $deleteItems = $this->db->prepare('DELETE FROM sale_items WHERE sale_id = ?');
            $deleteItems->execute([$id]);

            $deleteSale = $this->db->prepare('DELETE FROM sales WHERE id = ?');
            $deleteSale->execute([$id]);

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
