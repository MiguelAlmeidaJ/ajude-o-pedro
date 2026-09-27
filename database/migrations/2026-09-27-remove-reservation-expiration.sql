ALTER TABLE orders
    MODIFY reserved_until DATETIME NULL;

UPDATE orders
SET reserved_until = NULL
WHERE status = 'pending';

UPDATE raffle_numbers rn
JOIN orders o ON o.id = rn.order_id
SET rn.reserved_until = NULL
WHERE o.status = 'pending'
  AND rn.status = 'reserved';
