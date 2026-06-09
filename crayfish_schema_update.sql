-- ═══════════════════════════════════════════════════════════════
-- CRAYFISH ORDER SYSTEM — Schema update
-- Adds payment tracking to crayfish_orders + creates crayfish_payments table
-- Run this ONCE in phpMyAdmin or mysql CLI
-- ═══════════════════════════════════════════════════════════════

-- ── 1. Add payment columns to crayfish_orders ──
ALTER TABLE `crayfish_orders`
  ADD COLUMN `payment_status_id` bigint UNSIGNED NOT NULL DEFAULT 1
    COMMENT '1=Unpaid, 2=Partial, 3=Paid, 4=Refunded'
    AFTER `total_amount`,
  ADD COLUMN `payment_method_id` bigint UNSIGNED DEFAULT NULL
    AFTER `payment_status_id`,
  ADD COLUMN `amount_paid` decimal(10,2) NOT NULL DEFAULT '0.00'
    AFTER `payment_method_id`;

-- ── 2. Add FK for payment_status_id ──
ALTER TABLE `crayfish_orders`
  ADD CONSTRAINT `fk_crayfish_payment_status`
    FOREIGN KEY (`payment_status_id`) REFERENCES `payment_statuses` (`payment_status_id`);

-- ── 3. Add FK for payment_method_id ──
ALTER TABLE `crayfish_orders`
  ADD CONSTRAINT `fk_crayfish_payment_method`
    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`payment_method_id`);

-- ── 4. Create crayfish_payments table (payment history) ──
CREATE TABLE IF NOT EXISTS `crayfish_payments` (
  `payment_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint UNSIGNED NOT NULL,
  `payment_method_id` bigint UNSIGNED NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `fk_cpay_order` (`order_id`),
  KEY `fk_cpay_method` (`payment_method_id`),
  CONSTRAINT `fk_cpay_order` FOREIGN KEY (`order_id`) REFERENCES `crayfish_orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cpay_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`payment_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
