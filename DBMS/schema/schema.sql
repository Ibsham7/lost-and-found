create database nustlostandfound ;
use nustlostandfound ;
-- USERS TABLE
CREATE TABLE Users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    profile_pic VARCHAR(255),
    is_admin BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    date_registered TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- DEPARTMENTS TABLE
CREATE TABLE Departments (
    department_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(100)
);

-- CATEGORIES TABLE
CREATE TABLE Categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(100)
);

-- ITEMS TABLE
CREATE TABLE Items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    location VARCHAR(150),
    status ENUM('lost', 'found', 'claimed') DEFAULT 'lost',
    date_found DATE,
    user_id INT,
    department_id INT,
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (department_id) REFERENCES Departments(department_id),
    FOREIGN KEY (category_id) REFERENCES Categories(category_id)
);

-- ITEM IMAGES TABLE
CREATE TABLE ItemImages (
    image_id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT,
    image_url VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES Items(item_id)
);

-- CLAIMS TABLE
CREATE TABLE Claims (
    claim_id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT,
    claim_by INT,
    proof_of_ownership TEXT,
    claim_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    claim_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES Items(item_id),
    FOREIGN KEY (claim_by) REFERENCES Users(user_id)
);

-- MESSAGES TABLE (Two-way chat)
CREATE TABLE Messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT,
    recipient_id INT,
    message_text TEXT NOT NULL,
    timeStamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES Users(user_id),
    FOREIGN KEY (recipient_id) REFERENCES Users(user_id)
);

-- NOTIFICATIONS TABLE
CREATE TABLE Notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    message TEXT NOT NULL,
    seen BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);
-- Trigger: Update item status to 'claimed' when claim is approved
DELIMITER //
CREATE TRIGGER after_claim_approval
AFTER UPDATE ON Claims
FOR EACH ROW
BEGIN
    IF NEW.claim_status <> OLD.claim_status AND NEW.claim_status = 'approved' THEN
        UPDATE Items
        SET status = 'claimed'
        WHERE item_id = NEW.item_id;

        INSERT INTO Notifications (user_id, message)
        VALUES (
            NEW.claim_by,
            CONCAT('Your claim for item ID ', NEW.item_id, ' has been approved.')
        );
    END IF;
END; //
DELIMITER ;

-- Trigger: Prevent inserting claim on already claimed item
DELIMITER //
CREATE TRIGGER before_claim_insert
BEFORE INSERT ON Claims
FOR EACH ROW
BEGIN
    DECLARE item_status VARCHAR(20);
    SELECT status INTO item_status FROM Items WHERE item_id = NEW.item_id;
    IF item_status = 'claimed' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot add claim: Item is already claimed.';
    END IF;
END; //
DELIMITER ;

-- Trigger: Notify user on claim rejection
DELIMITER //
CREATE TRIGGER after_claim_rejection
AFTER UPDATE ON Claims
FOR EACH ROW
BEGIN
    IF NEW.claim_status <> OLD.claim_status AND NEW.claim_status = 'rejected' THEN
        INSERT INTO Notifications (user_id, message)
        VALUES (
            NEW.claim_by,
            CONCAT('Your claim for item ID ', NEW.item_id, ' has been rejected.')
        );
    END IF;
END; //
DELIMITER ;

-- Trigger: Notify admins on new item posted
DELIMITER //
CREATE TRIGGER after_item_insert
AFTER INSERT ON Items
FOR EACH ROW
BEGIN
    -- Instead of selecting users with is_admin flag, select specific admin users
    -- For example: Select users with certain user_ids or a different admin role column
    -- This example adds notifications for user_id 'admin1' and 'admin2'
    INSERT INTO Notifications (user_id, message)
    VALUES 
        ('admin1', CONCAT('New item posted: ', NEW.title)),
        ('admin2', CONCAT('New item posted: ', NEW.title));
END; //
DELIMITER ;

