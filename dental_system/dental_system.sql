-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 27, 2026 at 07:57 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dental_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `chair_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `treatment` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `duration_minutes` int(11) DEFAULT 30,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `priority` enum('low','normal','high') DEFAULT 'normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `chair_id`, `appointment_date`, `appointment_time`, `treatment`, `status`, `notes`, `created_at`, `duration_minutes`, `reminder_sent`, `priority`) VALUES
(1, 1, 2, 1, '2026-09-26', '09:00:00', 'Dental Cleaning', 'completed', 'Routine checkup', '2026-09-22 05:20:39', 30, 0, 'normal'),
(2, 2, 3, 2, '2026-09-26', '10:00:00', 'Tooth Filling', 'confirmed', 'Upper molar cavity', '2026-09-23 05:20:39', 45, 0, 'normal'),
(3, 3, 2, 3, '2026-09-26', '11:30:00', 'Root Canal Treatment', 'confirmed', 'Severe pain', '2026-09-24 05:20:39', 90, 0, 'high'),
(4, 4, 3, 4, '2026-09-27', '09:30:00', 'X-Ray', 'pending', 'Diagnostic X-ray', '2026-09-25 05:20:39', 15, 0, 'normal'),
(5, 5, 2, 5, '2026-09-27', '14:00:00', 'Dental Crown', 'pending', 'Crown fitting', '2026-09-25 05:20:39', 60, 0, 'normal'),
(6, 6, 3, 6, '2026-09-28', '10:30:00', 'Teeth Whitening', 'confirmed', 'Cosmetic procedure', '2026-09-26 05:20:39', 60, 0, 'low'),
(7, 7, 2, 7, '2026-09-29', '15:00:00', 'Braces Consultation', 'pending', 'Initial assessment', '2026-09-27 05:20:39', 30, 0, 'normal'),
(8, 8, 3, 8, '2026-09-24', '11:00:00', 'Scaling & Polishing', 'completed', 'Deep cleaning done', '2026-09-20 05:20:39', 45, 0, 'normal'),
(9, 9, 2, 9, '2026-09-23', '16:30:00', 'Tooth Extraction', 'cancelled', 'Patient requested cancel', '2026-09-19 05:20:39', 30, 0, 'low'),
(10, 10, 3, 10, '2026-09-21', '08:30:00', 'Dental Cleaning', 'completed', 'Regular checkup', '2026-09-17 05:20:39', 30, 0, 'normal');

-- --------------------------------------------------------

--
-- Table structure for table `chairs`
--

CREATE TABLE `chairs` (
  `id` int(11) NOT NULL,
  `chair_name` varchar(50) NOT NULL,
  `status` enum('available','occupied','maintenance') DEFAULT 'available',
  `current_patient_id` int(11) DEFAULT NULL,
  `current_doctor_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `location` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chairs`
--

INSERT INTO `chairs` (`id`, `chair_name`, `status`, `current_patient_id`, `current_doctor_id`, `updated_at`, `location`, `notes`) VALUES
(1, 'Chair 01', 'available', NULL, NULL, '2026-09-27 03:59:34', NULL, NULL),
(2, 'Chair 02', 'available', NULL, NULL, '2026-09-27 03:59:34', NULL, NULL),
(3, 'Chair 03', 'available', NULL, NULL, '2026-09-27 03:59:34', NULL, NULL),
(4, 'Chair 04', 'available', NULL, NULL, '2026-09-27 03:59:34', NULL, NULL),
(5, 'Chair 05', 'available', NULL, NULL, '2026-09-27 03:59:34', NULL, NULL),
(6, 'Chair 06', 'available', NULL, NULL, '2026-09-27 03:59:34', NULL, NULL),
(7, 'Chair 07', 'available', NULL, NULL, '2026-09-27 03:59:34', NULL, NULL),
(8, 'Chair 08', 'available', NULL, NULL, '2026-09-27 03:59:34', NULL, NULL),
(9, 'Chair 09', 'available', NULL, NULL, '2026-09-27 03:59:34', NULL, NULL),
(10, 'Chair 10', 'available', NULL, NULL, '2026-09-27 03:59:34', NULL, NULL),
(11, 'Chair 11', 'available', NULL, NULL, '2026-09-27 03:59:34', NULL, NULL),
(12, 'Chair 12', 'available', NULL, NULL, '2026-09-27 03:59:34', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `dental_chart`
--

CREATE TABLE `dental_chart` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `tooth_number` varchar(10) DEFAULT NULL,
  `condition_type` enum('healthy','cavity','filled','missing','crown','root_canal') DEFAULT 'healthy',
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(30) DEFAULT 'pcs',
  `min_stock` int(11) DEFAULT 10,
  `price` decimal(10,2) DEFAULT 0.00,
  `supplier` varchar(150) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(30) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `payment_method` enum('cash','card','online') DEFAULT 'cash',
  `status` enum('paid','partial','unpaid') DEFAULT 'unpaid',
  `invoice_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tax` decimal(10,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `patient_id`, `total_amount`, `discount`, `paid_amount`, `balance`, `payment_method`, `status`, `invoice_date`, `created_at`, `tax`, `created_by`) VALUES
(1, 'INV-20260920-001', 1, 3000.00, 0.00, 3000.00, 0.00, 'cash', 'paid', '2026-09-21', '2026-09-27 05:20:39', 0.00, 1),
(2, 'INV-20260921-002', 2, 5000.00, 200.00, 4800.00, 0.00, 'card', 'paid', '2026-09-22', '2026-09-27 05:20:39', 0.00, 1),
(3, 'INV-20260922-003', 3, 15000.00, 500.00, 8000.00, 6500.00, 'cash', 'partial', '2026-09-23', '2026-09-27 05:20:39', 0.00, 1),
(4, 'INV-20260923-004', 4, 1500.00, 0.00, 1500.00, 0.00, 'cash', 'paid', '2026-09-24', '2026-09-27 05:20:39', 0.00, 1),
(5, 'INV-20260924-005', 5, 25000.00, 1000.00, 0.00, 24000.00, 'card', 'unpaid', '2026-09-25', '2026-09-27 05:20:39', 0.00, 1),
(6, 'INV-20260925-006', 6, 12000.00, 0.00, 12000.00, 0.00, 'online', 'paid', '2026-09-26', '2026-09-27 05:20:39', 0.00, 1),
(7, 'INV-20260925-007', 7, 2500.00, 0.00, 2500.00, 0.00, 'cash', 'paid', '2026-09-26', '2026-09-27 05:20:39', 0.00, 1),
(8, 'INV-20260926-008', 8, 4500.00, 0.00, 2000.00, 2500.00, 'cash', 'partial', '2026-09-26', '2026-09-27 05:20:39', 0.00, 1),
(9, 'INV-20260926-009', 9, 4000.00, 0.00, 4000.00, 0.00, 'cash', 'paid', '2026-09-26', '2026-09-27 05:20:39', 0.00, 1),
(10, 'INV-20260926-010', 10, 3000.00, 0.00, 0.00, 3000.00, 'card', 'unpaid', '2026-09-26', '2026-09-27 05:20:39', 0.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications_log`
--

CREATE TABLE `notifications_log` (
  `id` int(11) NOT NULL,
  `type` enum('sms','email','system') DEFAULT 'system',
  `recipient` varchar(150) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `patient_code` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `nic` varchar(20) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `age` int(11) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `patient_code`, `full_name`, `nic`, `phone`, `email`, `address`, `gender`, `dob`, `blood_group`, `medical_history`, `created_at`, `age`, `emergency_contact`, `allergies`, `photo`, `occupation`, `status`, `updated_at`) VALUES
(1, 'P0001', 'Sunil Fernando', '199012345678', '0712345678', 'sunil@gmail.com', 'No 45, Galle Road, Colombo 03', 'male', '1990-05-15', 'O+', 'No major issues', '2026-07-29 05:20:39', 36, '0711111111', 'None', NULL, 'Engineer', 'active', '2026-09-27 05:20:39'),
(2, 'P0002', 'Anoma Silva', '198523456789', '0723456789', 'anoma@gmail.com', 'No 12, Temple Road, Kandy', 'female', '1985-08-20', 'A+', 'Diabetic (Type 2)', '2026-08-03 05:20:39', 41, '0722222222', 'Penicillin', NULL, 'Teacher', 'active', '2026-09-27 05:20:39'),
(3, 'P0003', 'Ravi Perera', '199534567890', '0734567890', 'ravi@gmail.com', 'No 78, Main Street, Galle', 'male', '1995-12-10', 'B+', 'Hypertension', '2026-08-08 05:20:39', 30, '0733333333', 'None', NULL, 'Businessman', 'active', '2026-09-27 05:20:39'),
(4, 'P0004', 'Kumari Jayawardena', '198845678901', '0745678901', 'kumari@gmail.com', 'No 22, Lake Drive, Negombo', 'female', '1988-03-25', 'AB+', 'None', '2026-08-13 05:20:39', 38, '0744444444', 'Sulfa drugs', NULL, 'Nurse', 'active', '2026-09-27 05:20:39'),
(5, 'P0005', 'Nimal Rajapaksa', '197956789012', '0756789012', 'nimal@gmail.com', 'No 89, Hill Street, Nuwara Eliya', 'male', '1979-07-18', 'O-', 'Cholesterol', '2026-08-18 05:20:39', 47, '0755555555', 'Aspirin', NULL, 'Accountant', 'active', '2026-09-27 05:20:39'),
(6, 'P0006', 'Chamari Dias', '200167890123', '0767890123', 'chamari@gmail.com', 'No 34, Beach Road, Mount Lavinia', 'female', '2001-11-05', 'A-', 'None', '2026-08-23 05:20:39', 24, '0766666666', 'None', NULL, 'Student', 'active', '2026-09-27 05:20:39'),
(7, 'P0007', 'Saman Wickrama', '199278901234', '0778901234', 'saman@gmail.com', 'No 56, Garden Road, Colombo 07', 'male', '1992-02-14', 'B-', 'Asthma (mild)', '2026-08-28 05:20:39', 34, '0777777777', 'Dust', NULL, 'Software Eng', 'active', '2026-09-27 05:20:39'),
(8, 'P0008', 'Dilani Weerasinghe', '199089012345', '0789012345', 'dilani@gmail.com', 'No 102, Flower Road, Colombo 07', 'female', '1990-09-22', 'AB-', 'None', '2026-09-02 05:20:39', 36, '0788888888', 'Latex', NULL, 'Doctor', 'active', '2026-09-27 05:20:39'),
(9, 'P0009', 'Kasun Bandara', '199890123456', '0790123456', 'kasun@gmail.com', 'No 15, Station Road, Ragama', 'male', '1998-06-30', 'O+', 'None', '2026-09-07 05:20:39', 28, '0799999999', 'Seafood', NULL, 'Chef', 'active', '2026-09-27 05:20:39'),
(10, 'P0010', 'Sanduni Peris', '199301234567', '0701234567', 'sanduni@gmail.com', 'No 67, Church Road, Moratuwa', 'female', '1993-04-12', 'A+', 'Migraine', '2026-09-12 05:20:39', 33, '0701111111', 'None', NULL, 'Designer', 'active', '2026-09-27 05:20:39');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','online','cheque') DEFAULT 'cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `invoice_id`, `amount`, `payment_method`, `reference_no`, `notes`, `paid_at`) VALUES
(1, 1, 3000.00, 'cash', 'CASH-001', 'Full payment', '2026-09-22 05:20:39'),
(2, 2, 4800.00, 'card', 'CARD-4521', 'Card payment', '2026-09-23 05:20:39'),
(3, 3, 8000.00, 'cash', 'CASH-002', 'Partial payment', '2026-09-24 05:20:39'),
(4, 4, 1500.00, 'cash', 'CASH-003', 'X-ray payment', '2026-09-25 05:20:39'),
(5, 6, 12000.00, 'online', 'ONL-7894', 'Online transfer', '2026-09-26 05:20:39'),
(6, 7, 2500.00, 'cash', 'CASH-004', 'Consultation fee', '2026-09-27 05:20:39'),
(7, 8, 2000.00, 'cash', 'CASH-005', 'Partial payment', '2026-09-27 05:20:39'),
(8, 9, 4000.00, 'cash', 'CASH-006', 'Extraction payment', '2026-09-27 05:20:39'),
(9, 1, 0.00, 'cash', '-', 'Adjustment', '2026-09-23 05:20:39'),
(10, 2, 0.00, 'card', '-', 'Adjustment', '2026-09-24 05:20:39');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `prescription_no` varchar(30) DEFAULT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `treatment_id` int(11) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `medicines` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `next_visit` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `prescription_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `prescription_no`, `patient_id`, `doctor_id`, `treatment_id`, `diagnosis`, `medicines`, `instructions`, `next_visit`, `notes`, `prescription_date`, `created_at`) VALUES
(1, 'RX-20260920-001', 1, 2, NULL, 'Routine checkup - healthy', '1. Vitamin C 500mg - 1x daily - 30 days', 'Maintain oral hygiene', '2027-03-25', NULL, '2026-09-21', '2026-09-27 05:21:33'),
(2, 'RX-20260921-002', 2, 3, NULL, 'Dental cavity - upper molar', '1. Amoxicillin 500mg - 3x daily - 5 days\n2. Paracetamol 500mg - 2x daily - 3 days', 'Avoid cold drinks', '2026-10-03', NULL, '2026-09-22', '2026-09-27 05:21:33'),
(3, 'RX-20260922-003', 3, 2, NULL, 'Severe pulpitis', '1. Ibuprofen 400mg - 3x daily - 5 days\n2. Amoxicillin 500mg - 3x daily - 7 days', 'Soft food only', '2026-09-29', NULL, '2026-09-23', '2026-09-27 05:21:33'),
(4, 'RX-20260923-004', 4, 3, NULL, 'Diagnostic imaging', '1. Chlorhexidine mouthwash - 2x daily', 'Rinse after meals', '2026-10-10', NULL, '2026-09-24', '2026-09-27 05:21:33'),
(5, 'RX-20260924-005', 5, 2, NULL, 'Crown preparation', '1. Paracetamol 1g - 3x daily - 3 days', 'Avoid hard food', '2026-10-06', NULL, '2026-09-25', '2026-09-27 05:21:33'),
(6, 'RX-20260925-006', 6, 3, NULL, 'Teeth staining', '1. Sensitivity toothpaste', 'Use twice daily', NULL, NULL, '2026-09-26', '2026-09-27 05:21:33'),
(7, 'RX-20260925-007', 7, 2, NULL, 'Malocclusion', '1. Vitamin D 1000IU - 1x daily', 'Regular braces checkup', '2026-10-26', NULL, '2026-09-26', '2026-09-27 05:21:33'),
(8, 'RX-20260926-008', 8, 3, NULL, 'Gingivitis', '1. Metronidazole 400mg - 3x daily - 5 days\n2. Chlorhexidine mouthwash', 'Warm salt water rinse', '2026-10-10', NULL, '2026-09-26', '2026-09-27 05:21:33'),
(9, 'RX-20260926-009', 9, 2, NULL, 'Impacted tooth', '1. Amoxicillin 500mg - 3x daily - 5 days\n2. Ibuprofen 400mg - 2x daily - 3 days', 'Cold compress', '2026-10-01', NULL, '2026-09-26', '2026-09-27 05:21:33'),
(10, 'RX-20260926-010', 10, 3, NULL, 'Preventive cleaning', '1. Fluoride gel application', 'Avoid eating 30 mins', '2027-03-25', NULL, '2026-09-26', '2026-09-27 05:21:33');

-- --------------------------------------------------------

--
-- Table structure for table `treatments`
--

CREATE TABLE `treatments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `chair_id` int(11) DEFAULT NULL,
  `treatment_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `treatment_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treatments`
--

INSERT INTO `treatments` (`id`, `patient_id`, `doctor_id`, `chair_id`, `treatment_name`, `description`, `cost`, `treatment_date`, `created_at`) VALUES
(1, 1, 2, 1, 'Dental Cleaning', 'Routine cleaning completed', 3000.00, '2026-09-26', '2026-09-27 05:20:39'),
(2, 2, 3, 2, 'Tooth Filling', 'Composite filling upper right', 5000.00, '2026-09-26', '2026-09-27 05:20:39'),
(3, 3, 2, 3, 'Root Canal Treatment', 'Molar root canal done', 15000.00, '2026-09-26', '2026-09-27 05:20:39'),
(4, 8, 3, 8, 'Scaling & Polishing', 'Deep cleaning', 4500.00, '2026-09-24', '2026-09-27 05:20:39'),
(5, 10, 3, 10, 'Dental Cleaning', 'Regular maintenance', 3000.00, '2026-09-21', '2026-09-27 05:20:39'),
(6, 4, 2, 4, 'X-Ray', 'Full mouth X-ray', 1500.00, '2026-09-20', '2026-09-27 05:20:39'),
(7, 5, 3, 5, 'Dental Crown', 'Porcelain crown', 25000.00, '2026-09-18', '2026-09-27 05:20:39'),
(8, 6, 2, 6, 'Teeth Whitening', 'Office whitening session', 12000.00, '2026-09-16', '2026-09-27 05:20:39'),
(9, 7, 3, 7, 'Braces Consultation', 'Initial consultation', 2500.00, '2026-09-14', '2026-09-27 05:20:39'),
(10, 9, 2, 9, 'Tooth Extraction', 'Extraction done under local', 4000.00, '2026-09-11', '2026-09-27 05:20:39');

-- --------------------------------------------------------

--
-- Table structure for table `treatments_catalog`
--

CREATE TABLE `treatments_catalog` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `duration_minutes` int(11) DEFAULT 30,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treatments_catalog`
--

INSERT INTO `treatments_catalog` (`id`, `name`, `category`, `description`, `price`, `duration_minutes`, `status`, `created_at`) VALUES
(1, 'Dental Cleaning', 'Preventive', 'Standard cleaning and scaling', 3000.00, 30, 1, '2026-09-27 05:20:39'),
(2, 'Tooth Filling', 'Restorative', 'Composite filling', 5000.00, 45, 1, '2026-09-27 05:20:39'),
(3, 'Root Canal Treatment', 'Endodontics', 'Root canal therapy', 15000.00, 90, 1, '2026-09-27 05:20:39'),
(4, 'Tooth Extraction', 'Surgery', 'Simple tooth extraction', 4000.00, 30, 1, '2026-09-27 05:20:39'),
(5, 'Dental Crown', 'Prosthodontics', 'Porcelain crown fitting', 25000.00, 60, 1, '2026-09-27 05:20:39'),
(6, 'Teeth Whitening', 'Cosmetic', 'Professional whitening', 12000.00, 60, 1, '2026-09-27 05:20:39'),
(7, 'Braces Consultation', 'Orthodontics', 'Initial braces assessment', 2500.00, 30, 1, '2026-09-27 05:20:39'),
(8, 'X-Ray', 'Diagnostic', 'Dental X-ray', 1500.00, 15, 1, '2026-09-27 05:20:39'),
(9, 'Scaling & Polishing', 'Preventive', 'Deep scaling', 4500.00, 45, 1, '2026-09-27 05:20:39'),
(10, 'Denture - Full Set', 'Prosthodontics', 'Complete denture', 45000.00, 120, 1, '2026-09-27 05:20:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','doctor','receptionist') DEFAULT 'receptionist',
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT 0.00,
  `theme_color` varchar(20) DEFAULT 'purple',
  `dark_mode` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `email`, `phone`, `status`, `created_at`, `profile_image`, `last_login`, `specialization`, `salary`, `theme_color`, `dark_mode`) VALUES
(1, 'admin', '$2y$10$wcoeMKhJfcAPU.tpRXA9RO1BJhxXb.E6QhDYjQzmaoLJoq53piMfe', 'System Admin', 'admin', 'admin@dental.com', '0111111111', 1, '2026-09-27 03:59:34', NULL, NULL, NULL, 0.00, 'purple', 0),
(2, 'doctor1', '$2y$10$wcoeMKhJfcAPU.tpRXA9RO1BJhxXb.E6QhDYjQzmaoLJoq53piMfe', 'Dr. Kamal Silva', 'doctor', 'kamal@dental.com', '0771111111', 1, '2026-09-27 03:59:34', NULL, NULL, NULL, 0.00, 'purple', 0),
(3, 'doctor2', '$2y$10$wcoeMKhJfcAPU.tpRXA9RO1BJhxXb.E6QhDYjQzmaoLJoq53piMfe', 'Dr. Nimal Perera', 'doctor', 'nimal@dental.com', '0772222222', 1, '2026-09-27 03:59:34', NULL, NULL, NULL, 0.00, 'purple', 0),
(4, 'reception', '$2y$10$wcoeMKhJfcAPU.tpRXA9RO1BJhxXb.E6QhDYjQzmaoLJoq53piMfe', 'Receptionist', 'receptionist', 'recep@dental.com', '0773333333', 1, '2026-09-27 03:59:34', NULL, NULL, NULL, 0.00, 'purple', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chairs`
--
ALTER TABLE `chairs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dental_chart`
--
ALTER TABLE `dental_chart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications_log`
--
ALTER TABLE `notifications_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_code` (`patient_code`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prescription_no` (`prescription_no`);

--
-- Indexes for table `treatments`
--
ALTER TABLE `treatments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `treatments_catalog`
--
ALTER TABLE `treatments_catalog`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `chairs`
--
ALTER TABLE `chairs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `dental_chart`
--
ALTER TABLE `dental_chart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications_log`
--
ALTER TABLE `notifications_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `treatments`
--
ALTER TABLE `treatments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `treatments_catalog`
--
ALTER TABLE `treatments_catalog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
