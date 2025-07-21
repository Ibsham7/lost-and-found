DELIMITER //
CREATE TRIGGER after_item_insert
AFTER INSERT ON Items
FOR EACH ROW
BEGIN
    -- Instead of selecting users with is_admin flag, insert notifications for specific admin users
    INSERT INTO Notifications (user_id, message)
    VALUES 
        (1, CONCAT('New item posted: ', NEW.title)),
        (2, CONCAT('New item posted: ', NEW.title));
END //
DELIMITER ; 