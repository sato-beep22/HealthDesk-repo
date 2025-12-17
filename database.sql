

CREATE DATABASE IF NOT EXISTS healthdesk;
USE healthdesk;


CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_staff_id VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Staff') NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    age INT,
    sex ENUM('Male', 'Female', 'Other'),
    date_of_birth DATE,
    place_of_birth VARCHAR(100),
    home_address TEXT,
    citizenship VARCHAR(50),
    civil_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    parent_guardian VARCHAR(100),
    contact_no VARCHAR(20),
 
    height DECIMAL(5,2), 
    weight DECIMAL(5,2), 
    bmi DECIMAL(4,2),
    bp VARCHAR(20), 
    temp DECIMAL(4,1), 
    pr INT, 
    rr INT, 
    heent TEXT, 
    chest TEXT,
    heart TEXT,
    lungs TEXT,
    abdomen TEXT,
    genital TEXT,
    skin TEXT,
    
    chronic_illness TEXT,
    allergies TEXT,
    operations TEXT,
    accidents_injuries TEXT,
    medicines_regular TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE inventory (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    category ENUM('Medicine', 'First Aid', 'Equipment') NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(20) NOT NULL,
    expiration_date DATE,
    status ENUM('In Stock', 'Low Stock', 'Critical') DEFAULT 'In Stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE dispensation_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_disbursed INT NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (item_id) REFERENCES inventory(item_id)
);


CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    report_content TEXT NOT NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
);


INSERT INTO users (admin_staff_id, password_hash, role, name, email) VALUES
('admin001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Jehu Admin', 'admin@healthdesk.com'),
('staff001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'Jane Staff', 'staff@healthdesk.com');

INSERT INTO patients (last_name, first_name, middle_name, age, sex, date_of_birth, place_of_birth, home_address, citizenship, civil_status, parent_guardian, contact_no, height, weight, bmi, bp, temp, pr, rr, heent, chest, heart, lungs, abdomen, genital, skin, chronic_illness, allergies, operations, accidents_injuries, medicines_regular) VALUES
('Doe', 'John', 'Michael', 20, 'Male', '2003-05-15', 'Manila', '123 University St, Quezon City', 'Filipino', 'Single', 'Jane Doe', '09123456789', 175.5, 70.2, 22.8, '120/80', 36.5, 72, 16, 'Normal', 'Clear', 'Regular rhythm', 'Clear', 'Soft', 'Normal', 'Clear', 'None', 'Penicillin', 'Appendectomy', 'None', 'None'),
('Smith', 'Alice', 'Rose', 19, 'Female', '2004-03-22', 'Cebu', '456 Campus Ave, Cebu City', 'Filipino', 'Single', 'Bob Smith', '09876543210', 162.0, 55.0, 21.0, '110/70', 36.8, 68, 14, 'Normal', 'Clear', 'Regular rhythm', 'Clear', 'Soft', 'Normal', 'Clear', 'Asthma', 'None', 'None', 'Sprained ankle', 'Inhaler');

INSERT INTO inventory (item_name, category, quantity, unit, expiration_date, status) VALUES
('Paracetamol', 'Medicine', 100, 'tablets', '2025-12-31', 'In Stock'),
('Bandages', 'First Aid', 50, 'rolls', '2024-06-30', 'Low Stock'),
('Stethoscope', 'Equipment', 5, 'pieces', NULL, 'In Stock'),
('Ibuprofen', 'Medicine', 25, 'tablets', '2024-08-15', 'Critical');
