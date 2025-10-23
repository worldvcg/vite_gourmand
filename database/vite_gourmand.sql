-- =========================================
-- VITE & GOURMAND — SCHEMA + SEED (MySQL)
-- Encodage / Charset
-- =========================================
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Si besoin, décommenter ces lignes :
-- CREATE DATABASE IF NOT EXISTS vite_gourmand
--   DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE vite_gourmand;

-- =========================================
-- RESET (drop dans le bon ordre)
-- =========================================
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS 
  order_items,
  reviews,
  contacts,
  opening_hours,
  password_resets,
  orders,
  dish_allergens,
  menu_dishes,
  allergens,
  dishes,
  menus,
  user_roles,
  roles,
  users;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================
-- TABLES
-- =========================================

-- 1) UTILISATEURS / RÔLES
CREATE TABLE users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name     VARCHAR(100) NOT NULL,
  last_name      VARCHAR(100) NOT NULL,
  email          VARCHAR(190) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  phone          VARCHAR(30)  NULL,
  address        VARCHAR(255) NULL,
  city           VARCHAR(120) NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE roles (
  id    TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(30) NOT NULL UNIQUE  -- 'user','employee','admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_roles (
  user_id INT UNSIGNED NOT NULL,
  role_id TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, role_id),
  CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) MENUS / PLATS / ALLERGENES
CREATE TABLE menus (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title            VARCHAR(150) NOT NULL,
  description      TEXT,
  theme            ENUM('Noël','Pâques','Classique','Évènement') NOT NULL DEFAULT 'Classique',
  regime           ENUM('classique','végétarien','vegan') NOT NULL DEFAULT 'classique',
  min_people       INT UNSIGNED NOT NULL DEFAULT 4,
  base_price       DECIMAL(10,2) NOT NULL,  -- prix pour min_people
  stock_available  INT UNSIGNED NOT NULL DEFAULT 0,
  conditions_text  TEXT,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dishes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150) NOT NULL,
  type        ENUM('entrée','plat','dessert') NOT NULL,
  description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE menu_dishes (
  menu_id  INT UNSIGNED NOT NULL,
  dish_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (menu_id, dish_id),
  CONSTRAINT fk_md_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
  CONSTRAINT fk_md_dish FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE allergens (
  id    TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dish_allergens (
  dish_id     INT UNSIGNED NOT NULL,
  allergen_id TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (dish_id, allergen_id),
  CONSTRAINT fk_da_dish FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE,
  CONSTRAINT fk_da_all  FOREIGN KEY (allergen_id) REFERENCES allergens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) COMMANDES
CREATE TABLE orders (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NOT NULL,
  customer_name   VARCHAR(150) NOT NULL,
  customer_email  VARCHAR(190) NOT NULL,
  customer_phone  VARCHAR(30)  NOT NULL,
  address         VARCHAR(255) NOT NULL,
  city            VARCHAR(120) NOT NULL,
  event_date      DATE NOT NULL,
  event_time      TIME NOT NULL,
  people_count    INT UNSIGNED NOT NULL,
  delivery_city_bordeaux TINYINT(1) NOT NULL DEFAULT 1,
  delivery_km     DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  price_menu      DECIMAL(10,2) NOT NULL,
  price_discount  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  price_delivery  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  price_total     DECIMAL(10,2) NOT NULL,
  status ENUM('nouvelle','acceptée','en préparation','en cours de livraison','livré','attente matériel','terminée','annulée')
         NOT NULL DEFAULT 'nouvelle',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_o_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_items (
  order_id INT UNSIGNED NOT NULL,
  menu_id  INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (order_id, menu_id),
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_menu  FOREIGN KEY (menu_id)  REFERENCES menus(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) AVIS
CREATE TABLE reviews (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  menu_id    INT UNSIGNED NOT NULL,
  rating     TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment    TEXT NULL,
  is_valid   TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_r_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_r_menu FOREIGN KEY (menu_id) REFERENCES menus(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) CONTACTS + HORAIRES
CREATE TABLE contacts (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(190) NOT NULL,
  subject    VARCHAR(200) NOT NULL,
  message    TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE opening_hours (
  id     TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  day    ENUM('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche') NOT NULL,
  open   TIME NOT NULL,
  close  TIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) RESETS MDP (optionnel)
CREATE TABLE password_resets (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(190) NOT NULL,
  token      VARCHAR(190) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (email),
  INDEX (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- DONNEES DE DEMO
-- =========================================

-- Rôles
INSERT INTO roles (name) VALUES ('user'), ('employee'), ('admin');

-- Admin (hash de démo : à remplacer par password_hash en prod)
INSERT INTO users (first_name,last_name,email,password_hash,phone,city)
VALUES ('Admin','Démo','admin@demo.fr','$2y$10$DEMOhashRemplacerParPasswordHashVrai','0600000000','Bordeaux');

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u JOIN roles r ON r.name='admin' WHERE u.email='admin@demo.fr';

-- Menus
INSERT INTO menus (title,description,theme,regime,min_people,base_price,stock_available,conditions_text)
VALUES
('Menu Fête de Noël','Entrée + plat + dessert festifs','Noël','classique',6,180.00,10,'Commander 7 jours avant.'),
('Menu Vegan Fraîcheur','Assortiment 100% végétal','Classique','vegan',4,120.00,8,'Commander 3 jours avant.'),
('Brunch Pâques','Sélection printanière sucrée/salée','Pâques','végétarien',8,200.00,5,'Commander 5 jours avant.');

-- Plats
INSERT INTO dishes (name,type,description) VALUES
('Velouté de potimarron','entrée','Crème légère'),
('Risotto aux cèpes','plat','Crémeux'),
('Bûche chocolat','dessert','Gourmande'),
('Salade croquante','entrée','Vegan'),
('Curry de légumes','plat','Lait de coco'),
('Tarte fruits rouges','dessert','Acidulée');

-- Assoc menus ↔ plats
-- Noël
INSERT INTO menu_dishes (menu_id,dish_id)
SELECT m.id, d.id FROM menus m, dishes d
WHERE m.title='Menu Fête de Noël' AND d.name IN ('Velouté de potimarron','Risotto aux cèpes','Bûche chocolat');

-- Vegan
INSERT INTO menu_dishes (menu_id,dish_id)
SELECT m.id, d.id FROM menus m, dishes d
WHERE m.title='Menu Vegan Fraîcheur' AND d.name IN ('Salade croquante','Curry de légumes','Tarte fruits rouges');

-- Pâques
INSERT INTO menu_dishes (menu_id,dish_id)
SELECT m.id, d.id FROM menus m, dishes d
WHERE m.title='Brunch Pâques' AND d.name IN ('Salade croquante','Risotto aux cèpes','Tarte fruits rouges');

-- Allergènes
INSERT INTO allergens (name) VALUES ('Gluten'),('Lactose'),('Fruits à coque'),('Soja');

-- Bûche chocolat : lactose + fruits à coque
INSERT INTO dish_allergens (dish_id, allergen_id)
SELECT d.id, a.id FROM dishes d, allergens a
WHERE d.name='Bûche chocolat' AND a.name IN ('Lactose','Fruits à coque');

-- Horaires (pied de page)
INSERT INTO opening_hours (day,open,close) VALUES
('Lundi','09:00','18:00'),
('Mardi','09:00','18:00'),
('Mercredi','09:00','18:00'),
('Jeudi','09:00','18:00'),
('Vendredi','09:00','18:00'),
('Samedi','10:00','16:00'),
('Dimanche','10:00','14:00');
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;