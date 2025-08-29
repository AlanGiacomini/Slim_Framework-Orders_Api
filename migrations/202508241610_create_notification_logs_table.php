<?php

//Cria tabela de logs de notificações

$pdo->exec("
CREATE TABLE IF NOT EXISTS notification_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  level VARCHAR(10) NOT NULL,
  order_id INT NOT NULL,
  old_status VARCHAR(20),
  new_status VARCHAR(20),
  message TEXT,
  context JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id)
);
");