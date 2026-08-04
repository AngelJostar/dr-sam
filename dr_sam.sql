-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 01-08-2026 a las 15:42:13
-- Versión del servidor: 8.4.3
-- Versión de PHP: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dr_sam`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `appointments`
--

CREATE TABLE `appointments` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED DEFAULT NULL,
  `doctor_id` bigint UNSIGNED DEFAULT NULL,
  `medical_unit_id` bigint UNSIGNED DEFAULT NULL,
  `procedure_area_id` bigint UNSIGNED DEFAULT NULL,
  `specialty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modality` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `medical_unit_id`, `procedure_area_id`, `specialty`, `modality`, `location`, `status`, `starts_at`, `ends_at`, `reason`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, 'Medicina interna', 'video', NULL, 'scheduled', '2026-07-01 16:00:00', '2026-07-01 16:30:00', 'Consulta demo de seguimiento', NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(2, 1, 1, 1, NULL, 'Medicina interna', 'Video llamada', 'Consultorio virtual', 'scheduled', '2026-07-27 16:00:00', '2026-07-27 16:20:00', NULL, '{\"source\": \"doctor_module\", \"doctor_clinic_id\": 1}', '2026-07-27 18:33:29', '2026-07-27 18:33:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `appointment_status_events`
--

CREATE TABLE `appointment_status_events` (
  `id` bigint UNSIGNED NOT NULL,
  `appointment_id` bigint UNSIGNED NOT NULL,
  `changed_by` bigint UNSIGNED DEFAULT NULL,
  `from_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auditable_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `authorizations`
--

CREATE TABLE `authorizations` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `treatment_id` bigint UNSIGNED DEFAULT NULL,
  `hospitalization_id` bigint UNSIGNED DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `medical_request` text COLLATE utf8mb4_unicode_ci,
  `justification` text COLLATE utf8mb4_unicode_ci,
  `requested_at` date DEFAULT NULL,
  `responded_at` date DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'requested',
  `authorization_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorized_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `valid_until` date DEFAULT NULL,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `authorizations`
--

INSERT INTO `authorizations` (`id`, `patient_id`, `treatment_id`, `hospitalization_id`, `type`, `medical_request`, `justification`, `requested_at`, `responded_at`, `status`, `authorization_number`, `authorized_amount`, `valid_until`, `observations`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, NULL, 1, 'hospitalization', 'Ingreso hospitalario por descompensacion.', 'Requiere vigilancia y ajuste terapeutico.', '2026-07-01', '2026-07-01', 'authorized', 'AUTH-HOSP-0001', 85000.00, '2026-07-08', NULL, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chronic_conditions`
--

CREATE TABLE `chronic_conditions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_cie10` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `chronic_conditions`
--

INSERT INTO `chronic_conditions` (`id`, `name`, `default_cie10`, `description`, `status`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Diabetes mellitus', 'E11', NULL, 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(2, 'Hipertension arterial', 'I10', NULL, 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(3, 'Insuficiencia renal cronica', 'N18', NULL, 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(4, 'Cancer', 'C80', NULL, 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(5, 'EPOC', 'J44', NULL, 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(6, 'Enfermedad cardiovascular', 'I25', NULL, 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(7, 'Artritis reumatoide', 'M06', NULL, 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(8, 'Esclerosis multiple', 'G35', NULL, 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(9, 'VIH', 'B24', NULL, 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(10, 'Otras', NULL, NULL, 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clinical_encounters`
--

CREATE TABLE `clinical_encounters` (
  `id` bigint UNSIGNED NOT NULL,
  `appointment_id` bigint UNSIGNED DEFAULT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `doctor_id` bigint UNSIGNED NOT NULL,
  `medical_unit_id` bigint UNSIGNED DEFAULT NULL,
  `procedure_area_id` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `symptoms` text COLLATE utf8mb4_unicode_ci,
  `vital_signs` json DEFAULT NULL,
  `background` json DEFAULT NULL,
  `examination` text COLLATE utf8mb4_unicode_ci,
  `assessment` text COLLATE utf8mb4_unicode_ci,
  `treatment_plan` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clinical_records`
--

CREATE TABLE `clinical_records` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `doctor_id` bigint UNSIGNED DEFAULT NULL,
  `record_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `summary` text COLLATE utf8mb4_unicode_ci,
  `payload` json DEFAULT NULL,
  `recorded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clinical_records`
--

INSERT INTO `clinical_records` (`id`, `patient_id`, `doctor_id`, `record_type`, `title`, `summary`, `payload`, `recorded_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'summary', 'Resumen clinico demo', 'Registro semilla para validar el historial clinico.', '{\"source\": \"seeder\"}', '2026-07-24 01:40:33', '2026-07-24 01:40:33', '2026-07-24 01:40:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contracted_services`
--

CREATE TABLE `contracted_services` (
  `id` bigint UNSIGNED NOT NULL,
  `institution_id` bigint UNSIGNED DEFAULT NULL,
  `medical_unit_id` bigint UNSIGNED DEFAULT NULL,
  `service_id` bigint UNSIGNED DEFAULT NULL,
  `contract_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `starts_at` date DEFAULT NULL,
  `ends_at` date DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `contracted_services`
--

INSERT INTO `contracted_services` (`id`, `institution_id`, `medical_unit_id`, `service_id`, `contract_number`, `status`, `starts_at`, `ends_at`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'DEMO-2026-1', 'active', '2026-01-01', '2026-12-31', '{\"review\": true}', '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(2, 1, 1, 2, 'DEMO-2026-2', 'active', '2026-01-01', '2026-12-31', '{\"review\": true}', '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(3, 1, 1, 3, 'DEMO-2026-3', 'active', '2026-01-01', '2026-12-31', '{\"review\": true}', '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(4, 1, 1, 4, 'DEMO-2026-4', 'active', '2026-01-01', '2026-12-31', '{\"review\": true}', '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(5, 1, 1, 5, 'DEMO-2026-5', 'active', '2026-01-01', '2026-12-31', '{\"review\": true}', '2026-07-24 01:39:26', '2026-07-24 01:39:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `delivery_reports`
--

CREATE TABLE `delivery_reports` (
  `id` bigint UNSIGNED NOT NULL,
  `delivery_route_id` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `payload` json DEFAULT NULL,
  `reported_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `delivery_routes`
--

CREATE TABLE `delivery_routes` (
  `id` bigint UNSIGNED NOT NULL,
  `messenger_profile_id` bigint UNSIGNED DEFAULT NULL,
  `provider_request_id` bigint UNSIGNED DEFAULT NULL,
  `patient_order_id` bigint UNSIGNED DEFAULT NULL,
  `route_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctors`
--

CREATE TABLE `doctors` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `medical_unit_id` bigint UNSIGNED DEFAULT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `professional_license` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subspecialty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `verified_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `medical_unit_id`, `external_id`, `full_name`, `professional_license`, `specialty`, `subspecialty`, `service_name`, `status`, `verified_at`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 20, 1, 'doc-carter', 'Dr. Carter Jimmy', 'CED-DEMO-2026', 'Medicina interna', 'Nutricion clinica', 'Nutricion parenteral', 'active', '2026-07-24 01:40:33', '{\"review\": true}', '2026-07-24 01:39:26', '2026-07-24 01:40:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctor_availability_exceptions`
--

CREATE TABLE `doctor_availability_exceptions` (
  `id` bigint UNSIGNED NOT NULL,
  `doctor_id` bigint UNSIGNED NOT NULL,
  `doctor_clinic_id` bigint UNSIGNED DEFAULT NULL,
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctor_availability_rules`
--

CREATE TABLE `doctor_availability_rules` (
  `id` bigint UNSIGNED NOT NULL,
  `doctor_id` bigint UNSIGNED NOT NULL,
  `doctor_clinic_id` bigint UNSIGNED NOT NULL,
  `weekday` tinyint UNSIGNED NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_person',
  `recurrence_start` date NOT NULL,
  `recurrence_end` date DEFAULT NULL,
  `selected_months` json DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `doctor_availability_rules`
--

INSERT INTO `doctor_availability_rules` (`id`, `doctor_id`, `doctor_clinic_id`, `weekday`, `start_time`, `end_time`, `mode`, `recurrence_start`, `recurrence_end`, `selected_months`, `status`, `notes`, `metadata`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '09:00:00', '13:00:00', 'in_person', '2026-07-27', NULL, '[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]', 'published', NULL, '{\"period_mode\": \"all\"}', '2026-07-27 21:36:36', '2026-07-27 21:36:36', '2026-07-27 21:36:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctor_clinics`
--

CREATE TABLE `doctor_clinics` (
  `id` bigint UNSIGNED NOT NULL,
  `doctor_id` bigint UNSIGNED NOT NULL,
  `medical_unit_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'America/Mexico_City',
  `location_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_person',
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `doctor_clinics`
--

INSERT INTO `doctor_clinics` (`id`, `doctor_id`, `medical_unit_id`, `name`, `color`, `timezone`, `location_type`, `address`, `status`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Consultorio virtual', NULL, 'America/Mexico_City', 'virtual', 'Atención por videollamada', 'active', NULL, '2026-07-27 18:33:29', '2026-07-27 18:33:29'),
(2, 1, 1, 'CBTA', NULL, 'America/Mexico_City', 'in_person', 'México', 'active', '{\"rent\": \"332\", \"staff\": null, \"state\": null, \"street\": null, \"country\": \"México\", \"supplies\": null, \"utilities\": \"43\", \"fixed_phone\": \"443\", \"maintenance\": null, \"postal_code\": null, \"mobile_phone\": null, \"municipality\": null, \"neighborhood\": null, \"phone_number\": null, \"phone_prefix\": \"+52\", \"property_tax\": null, \"location_scope\": \"particular\", \"exterior_number\": null, \"interior_number\": null}', '2026-07-27 18:47:41', '2026-07-27 18:47:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documents`
--

CREATE TABLE `documents` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED DEFAULT NULL,
  `treatment_id` bigint UNSIGNED DEFAULT NULL,
  `medication_delivery_id` bigint UNSIGNED DEFAULT NULL,
  `hospitalization_id` bigint UNSIGNED DEFAULT NULL,
  `authorization_id` bigint UNSIGNED DEFAULT NULL,
  `invoice_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_mime` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint UNSIGNED DEFAULT NULL,
  `uploaded_by` bigint UNSIGNED DEFAULT NULL,
  `loaded_at` timestamp NULL DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'current',
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `documents`
--

INSERT INTO `documents` (`id`, `patient_id`, `treatment_id`, `medication_delivery_id`, `hospitalization_id`, `authorization_id`, `invoice_id`, `name`, `document_type`, `file_path`, `file_mime`, `file_size`, `uploaded_by`, `loaded_at`, `expires_at`, `status`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, NULL, NULL, NULL, NULL, NULL, 'Poliza GMM demo', 'policy', NULL, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-12-31', 'current', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hospitalizations`
--

CREATE TABLE `hospitalizations` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `hospital_id` bigint UNSIGNED DEFAULT NULL,
  `doctor_id` bigint UNSIGNED DEFAULT NULL,
  `hospital_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admitted_at` timestamp NULL DEFAULT NULL,
  `discharged_at` timestamp NULL DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `admission_diagnosis` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discharge_diagnosis` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorization_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `stay_days` int UNSIGNED NOT NULL DEFAULT '0',
  `procedures_summary` text COLLATE utf8mb4_unicode_ci,
  `inpatient_medications` text COLLATE utf8mb4_unicode_ci,
  `studies_performed` text COLLATE utf8mb4_unicode_ci,
  `administrative_notes` text COLLATE utf8mb4_unicode_ci,
  `authorized_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `hospitalizations`
--

INSERT INTO `hospitalizations` (`id`, `patient_id`, `hospital_id`, `doctor_id`, `hospital_name`, `admitted_at`, `discharged_at`, `reason`, `admission_diagnosis`, `discharge_diagnosis`, `area`, `event_type`, `authorization_number`, `status`, `stay_days`, `procedures_summary`, `inpatient_medications`, `studies_performed`, `administrative_notes`, `authorized_amount`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 1, 'Hospital General Demo Dr. Sam', '2026-07-01 14:45:00', NULL, 'Descompensacion metabolica con requerimiento de vigilancia.', 'Diabetes mellitus descompensada', NULL, 'hospitalizacion', 'complication', 'AUTH-HOSP-0001', 'active', 5, 'Monitoreo metabolico y ajuste terapeutico.', 'Soluciones, insulina y medicamentos de soporte.', 'Laboratorio seriado.', 'Pendiente documentacion completa de egreso.', 85000.00, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hospitalization_daily_notes`
--

CREATE TABLE `hospitalization_daily_notes` (
  `id` bigint UNSIGNED NOT NULL,
  `hospitalization_id` bigint UNSIGNED NOT NULL,
  `note_date` date NOT NULL,
  `administrative_evolution` text COLLATE utf8mb4_unicode_ci,
  `general_clinical_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relevant_changes` text COLLATE utf8mb4_unicode_ci,
  `additional_requirements` text COLLATE utf8mb4_unicode_ci,
  `pending_studies` text COLLATE utf8mb4_unicode_ci,
  `pending_authorizations` text COLLATE utf8mb4_unicode_ci,
  `prolonged_stay_risk` tinyint(1) NOT NULL DEFAULT '0',
  `possible_discharge_date` date DEFAULT NULL,
  `captured_by` bigint UNSIGNED DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `hospitalization_daily_notes`
--

INSERT INTO `hospitalization_daily_notes` (`id`, `hospitalization_id`, `note_date`, `administrative_evolution`, `general_clinical_status`, `relevant_changes`, `additional_requirements`, `pending_studies`, `pending_authorizations`, `prolonged_stay_risk`, `possible_discharge_date`, `captured_by`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, '2026-07-05', 'Continua en vigilancia. Se solicita prorroga por estancia prolongada.', 'Estable con riesgo administrativo', NULL, NULL, 'Laboratorio de control', 'Prorroga de estancia', 1, '2026-07-07', NULL, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hospitalization_procedures`
--

CREATE TABLE `hospitalization_procedures` (
  `id` bigint UNSIGNED NOT NULL,
  `hospitalization_id` bigint UNSIGNED NOT NULL,
  `procedure_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `performed_at` timestamp NULL DEFAULT NULL,
  `doctor_id` bigint UNSIGNED DEFAULT NULL,
  `cost` decimal(14,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hospitals`
--

CREATE TABLE `hospitals` (
  `id` bigint UNSIGNED NOT NULL,
  `medical_unit_id` bigint UNSIGNED DEFAULT NULL,
  `provider_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rfc` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `network_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'network',
  `address` text COLLATE utf8mb4_unicode_ci,
  `contact_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `hospitals`
--

INSERT INTO `hospitals` (`id`, `medical_unit_id`, `provider_id`, `name`, `rfc`, `network_type`, `address`, `contact_phone`, `status`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 'Hospital General Demo Dr. Sam', 'HGD260101AB1', 'network', 'Unidad demo para revision de flujos', '5555550303', 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `institutions`
--

CREATE TABLE `institutions` (
  `id` bigint UNSIGNED NOT NULL,
  `owner_user_id` bigint UNSIGNED DEFAULT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `legal_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'institution',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `institutions`
--

INSERT INTO `institutions` (`id`, `owner_user_id`, `external_id`, `name`, `legal_name`, `type`, `status`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 3, 'inst-portal', 'Imss Bienestar Estado de Mexico', NULL, 'institution', 'active', '{\"source\": \"demo\"}', '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(2, 4, 'inst-angeles', 'Operadora de Hospitales Angeles', NULL, 'institution', 'active', '{\"source\": \"demo\"}', '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(3, 5, 'inst-imss-cdmx', 'Imss Bienestar Ciudad de Mexico', NULL, 'institution', 'active', '{\"source\": \"demo\"}', '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(4, 6, 'inst-t2', 'IMSS Bienestar Estado de Mexico', NULL, 'institution', 'active', '{\"source\": \"demo\"}', '2026-07-24 01:39:26', '2026-07-24 01:39:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insurance_advisor_notifications`
--

CREATE TABLE `insurance_advisor_notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `insurance_policy_id` bigint UNSIGNED NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `audience` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `occurred_at` timestamp NOT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `insurance_advisor_notifications`
--

INSERT INTO `insurance_advisor_notifications` (`id`, `insurance_policy_id`, `code`, `type`, `audience`, `subject`, `body`, `occurred_at`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 'advisor-message-1-20260801091726960', 'message', 'insured', 'Seguimiento de pÃ³liza', 'Te contactamos para dar seguimiento a tu pÃ³liza GMM-DRSAM-0001. Tu asesor puede ayudarte con la renovaciÃ³n y documentaciÃ³n pendiente.', '2026-08-01 15:17:26', '{\"sent_by\": 22}', '2026-08-01 15:17:26', '2026-08-01 15:17:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insurance_carriers`
--

CREATE TABLE `insurance_carriers` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aseguradora privada',
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scope` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insurance_policies`
--

CREATE TABLE `insurance_policies` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED DEFAULT NULL,
  `policy_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `insurer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plan_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `starts_at` date DEFAULT NULL,
  `ends_at` date DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `insurance_policies`
--

INSERT INTO `insurance_policies` (`id`, `patient_id`, `policy_number`, `insurer_name`, `plan_name`, `employer_name`, `status`, `starts_at`, `ends_at`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'GMM-DRSAM-0001', 'Aseguradora Salud Integral', 'GMM Corporativo Plus', 'Empresa Demo SA de CV', 'active', '2026-01-01', '2026-12-31', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` bigint UNSIGNED NOT NULL,
  `pharmacy_product_id` bigint UNSIGNED NOT NULL,
  `medical_unit_id` bigint UNSIGNED DEFAULT NULL,
  `warehouse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lot` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int UNSIGNED NOT NULL DEFAULT '0',
  `expires_at` date DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `pharmacy_product_id`, `medical_unit_id`, `warehouse`, `lot`, `quantity`, `expires_at`, `status`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Farmacia demo', 'L-DRSAM-001', 69, NULL, 'available', '{\"movements\": [{\"at\": \"2026-07-30T05:13:56.530692Z\", \"type\": \"prescription_dispense\", \"quantity\": -1, \"patient_order_item_id\": 1}]}', '2026-07-24 01:40:33', '2026-07-30 05:13:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `inventory_item_id` bigint UNSIGNED NOT NULL,
  `medical_unit_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int UNSIGNED NOT NULL,
  `stock_before` int UNSIGNED NOT NULL,
  `stock_after` int UNSIGNED NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inventory_movements`
--

INSERT INTO `inventory_movements` (`id`, `inventory_item_id`, `medical_unit_id`, `user_id`, `type`, `quantity`, `stock_before`, `stock_after`, `source`, `notes`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 'exit', 1, 70, 69, 'Receta surtida', 'Surtimiento de Paracetamol Tableta', '{\"patient_order_item_id\": 1}', '2026-07-30 05:13:56', '2026-07-30 05:13:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint UNSIGNED NOT NULL,
  `hospitalization_id` bigint UNSIGNED DEFAULT NULL,
  `hospital_id` bigint UNSIGNED DEFAULT NULL,
  `provider_id` bigint UNSIGNED DEFAULT NULL,
  `provider_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_rfc` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fiscal_uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `concept` text COLLATE utf8mb4_unicode_ci,
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `vat` decimal(14,2) NOT NULL DEFAULT '0.00',
  `withholdings` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'received',
  `paid_at` date DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `xml_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `invoices`
--

INSERT INTO `invoices` (`id`, `hospitalization_id`, `hospital_id`, `provider_id`, `provider_name`, `provider_rfc`, `invoice_number`, `fiscal_uuid`, `invoice_date`, `concept`, `subtotal`, `vat`, `withholdings`, `total`, `currency`, `status`, `paid_at`, `rejection_reason`, `xml_path`, `pdf_path`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 1, 'Hospital General Demo Dr. Sam', 'HGD260101AB1', 'FAC-HOSP-0001', '11111111-2222-3333-4444-555555555555', '2026-07-04', 'Servicios hospitalarios', 62000.00, 9920.00, 0.00, 71920.00, 'MXN', 'in_review', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` bigint UNSIGNED NOT NULL,
  `invoice_id` bigint UNSIGNED NOT NULL,
  `concept_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `quantity` int UNSIGNED NOT NULL DEFAULT '1',
  `unit_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `concept_type`, `description`, `quantity`, `unit_price`, `subtotal`, `total`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'room', 'Habitacion y servicios hospitalarios', 5, 12400.00, 62000.00, 71920.00, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medical_devices`
--

CREATE TABLE `medical_devices` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `manufacturer` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `connectivity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linked_module` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_data` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `compatibility` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medical_units`
--

CREATE TABLE `medical_units` (
  `id` bigint UNSIGNED NOT NULL,
  `institution_id` bigint UNSIGNED DEFAULT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clues` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `municipality` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `typology` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `care_level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `latitude` decimal(11,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `beds` int UNSIGNED NOT NULL DEFAULT '0',
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `partidas` json DEFAULT NULL,
  `subpartidas` json DEFAULT NULL,
  `source_sheets` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medical_units`
--

INSERT INTO `medical_units` (`id`, `institution_id`, `external_id`, `code`, `clues`, `name`, `city`, `municipality`, `state`, `entity`, `type`, `typology`, `care_level`, `address`, `latitude`, `longitude`, `beds`, `contact`, `unit_username`, `status`, `partidas`, `subpartidas`, `source_sheets`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 'demo-hospital-general-dr-sam', 'DRSAM-DEMO', 'DRSAM000001', 'Hospital General Demo Dr. Sam', 'Ciudad de Mexico', 'Benito Juarez', 'Ciudad de Mexico', 'Ciudad de Mexico', 'Hospital General', 'Hospital General', 'Segundo Nivel', 'Unidad demo para revision de flujos', NULL, NULL, 80, NULL, 'unidad.demo', 'maintenance', '[\"1\", \"13\"]', '[\"Nutricion parenteral\", \"Mezclas oncologicas\"]', '[\"Seeder demo\"]', '{\"review_scope\": true, \"procedure_areas\": [{\"id\": \"b21f0540-5837-4511-b5dd-08bc833741c5\", \"type\": \"consulting\", \"floor\": \"34\", \"capacity\": \"4\", \"location\": \"San Francisco 386\", \"schedule\": {\"friday\": {\"end\": \"16:00\", \"start\": \"08:00\", \"enabled\": \"1\"}, \"monday\": {\"end\": \"16:00\", \"start\": \"08:00\", \"enabled\": \"1\"}, \"sunday\": {\"end\": \"16:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"16:00\", \"start\": \"08:00\", \"enabled\": \"1\"}, \"saturday\": {\"end\": \"16:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"16:00\", \"start\": \"08:00\", \"enabled\": \"1\"}, \"wednesday\": {\"end\": \"16:00\", \"start\": \"08:00\", \"enabled\": \"1\"}}, \"responsible\": \"Responsable de Enfermeria\", \"unit_number\": \"C-01\"}], \"last_status_note\": \"Actualizado desde portal institucional\", \"last_status_changed_at\": \"2026-07-27T22:26:27.140732Z\", \"last_status_changed_by\": 1}', '2026-07-24 01:39:26', '2026-07-27 23:41:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medications`
--

CREATE TABLE `medications` (
  `id` bigint UNSIGNED NOT NULL,
  `pharmacy_product_id` bigint UNSIGNED DEFAULT NULL,
  `medication_catalog_item_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active_substance` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `presentation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_dose` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requires_authorization` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medications`
--

INSERT INTO `medications` (`id`, `pharmacy_product_id`, `medication_catalog_item_id`, `name`, `active_substance`, `presentation`, `default_dose`, `requires_authorization`, `status`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, NULL, 'Metformina', 'Metformina', 'Tableta 850 mg', '850 mg', 1, 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(2, NULL, NULL, 'Telmisartan', 'Telmisartan', 'Tableta 40 mg', '40 mg', 1, 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(3, 1, NULL, 'Paracetamol Tableta', 'Paracetamol', 'Tableta 500 mg', '500 mg', 0, 'active', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medication_catalog_items`
--

CREATE TABLE `medication_catalog_items` (
  `id` bigint UNSIGNED NOT NULL,
  `institution_id` bigint UNSIGNED DEFAULT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cnis` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `generic_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `therapeutic_group` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `presentation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requires_prescription` tinyint(1) NOT NULL DEFAULT '0',
  `controlled` tinyint(1) NOT NULL DEFAULT '0',
  `cold_chain` tinyint(1) NOT NULL DEFAULT '0',
  `sector_health` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medication_catalog_items`
--

INSERT INTO `medication_catalog_items` (`id`, `institution_id`, `external_id`, `cnis`, `name`, `generic_name`, `therapeutic_group`, `description`, `presentation`, `requires_prescription`, `controlled`, `cold_chain`, `sector_health`, `status`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 'med-cnis-010-000-0104-00', '010.000.0104.00', 'Paracetamol', 'Paracetamol', 'Analgesia', 'Tableta 500 mg, envase con 10 tabletas', 'Tableta', 0, 0, 0, 0, 'active', NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medication_deliveries`
--

CREATE TABLE `medication_deliveries` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `treatment_id` bigint UNSIGNED DEFAULT NULL,
  `medication_id` bigint UNSIGNED DEFAULT NULL,
  `provider_id` bigint UNSIGNED DEFAULT NULL,
  `quantity_delivered` int UNSIGNED NOT NULL DEFAULT '0',
  `covered_period` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_delivery_date` date NOT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `delivery_address` text COLLATE utf8mb4_unicode_ci,
  `delivery_responsible` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `delivery_evidence_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patient_acceptance` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medication_deliveries`
--

INSERT INTO `medication_deliveries` (`id`, `patient_id`, `treatment_id`, `medication_id`, `provider_id`, `quantity_delivered`, `covered_period`, `scheduled_delivery_date`, `actual_delivery_date`, `delivery_address`, `delivery_responsible`, `status`, `delivery_evidence_path`, `patient_acceptance`, `observations`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 1, 1, 60, '30 dias', '2026-07-10', NULL, 'Av. Insurgentes Sur 100, Ciudad de Mexico', 'Coordinacion entregas', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `messenger_profiles`
--

CREATE TABLE `messenger_profiles` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `messenger_profiles`
--

INSERT INTO `messenger_profiles` (`id`, `user_id`, `external_id`, `phone`, `vehicle`, `status`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 19, 'messenger-luis', '5555550202', 'Unidad refrigerada demo', 'active', '{\"review\": true}', '2026-07-24 01:40:33', '2026-07-24 01:40:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_06_30_000001_create_identity_and_support_tables', 1),
(2, '2026_06_30_000002_create_institution_unit_service_tables', 1),
(3, '2026_06_30_000003_create_care_tables', 1),
(4, '2026_06_30_000004_create_provider_pharmacy_order_tables', 1),
(5, '2026_06_30_000005_create_messaging_platform_audit_tables', 1),
(6, '2026_07_05_000001_create_insurance_health_module_tables', 1),
(7, '2026_07_20_000001_create_insurance_carriers_table', 1),
(8, '2026_07_20_000002_create_medical_devices_table', 1),
(9, '2026_07_21_000001_create_procedure_area_and_appointment_event_tables', 1),
(10, '2026_07_21_000002_create_doctor_availability_and_clinical_encounters', 1),
(11, '2026_07_26_000001_rename_import_identifiers_to_external_identifiers', 2),
(12, '2026_07_23_000001_create_external_pharmacy_inventory_management_tables', 3),
(13, '2026_08_01_000001_create_insurance_advisor_notifications_table', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `operational_areas`
--

CREATE TABLE `operational_areas` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_permissions` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `operational_areas`
--

INSERT INTO `operational_areas` (`id`, `key`, `label`, `role_label`, `default_permissions`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 'enfermeria', 'Enfermeria', 'Operador', '[\"view-history\", \"view-detail\", \"update-nursing\", \"download-reports\"]', NULL, '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(2, 'farmacia', 'Farmacia intrahospitalaria', 'Responsable de Area', '[\"view-history\", \"view-detail\", \"update-pharmacy-npt\", \"update-pharmacy-chemo\", \"download-reports\"]', NULL, '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(3, 'farmacia-externa', 'Farmacia Externa', 'Responsable de Area', '[\"view-history\", \"view-detail\", \"manage-external-pharmacy\", \"download-reports\"]', NULL, '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(4, 'oncologia', 'Centro Oncologico', 'Operador', '[\"view-history\", \"view-detail\", \"update-oncology\", \"download-reports\"]', NULL, '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(5, 'consulta', 'Consulta Externa', 'Operador', '[\"view-history\", \"view-detail\", \"update-outpatient\", \"download-reports\"]', NULL, '2026-07-24 01:39:26', '2026-07-24 01:39:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `operational_profiles`
--

CREATE TABLE `operational_profiles` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `medical_unit_id` bigint UNSIGNED DEFAULT NULL,
  `operational_area_id` bigint UNSIGNED DEFAULT NULL,
  `role_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `permissions` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `operational_profiles`
--

INSERT INTO `operational_profiles` (`id`, `user_id`, `medical_unit_id`, `operational_area_id`, `role_label`, `status`, `permissions`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 8, 1, 1, 'Operador', 'active', '[\"view-history\", \"view-detail\", \"update-nursing\", \"download-reports\"]', '{\"legacy_area\": \"Enfermeria\"}', '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(2, 9, 1, 2, 'Responsable de Area', 'active', '[\"view-history\", \"view-detail\", \"update-pharmacy-npt\", \"update-pharmacy-chemo\", \"download-reports\"]', '{\"legacy_area\": \"Farmacia intrahospitalaria\"}', '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(3, 10, 1, 3, 'Responsable de Area', 'active', '[\"view-history\", \"view-detail\", \"manage-external-pharmacy\", \"download-reports\"]', '{\"legacy_area\": \"Farmacia Externa\"}', '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(4, 13, 1, 4, 'Operador', 'active', '[\"view-history\", \"view-detail\", \"update-oncology\", \"download-reports\"]', '{\"legacy_area\": \"Centro Oncologico\"}', '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(5, 14, 1, 5, 'Operador', 'active', '[\"view-history\", \"view-detail\", \"update-outpatient\", \"download-reports\"]', '{\"legacy_area\": \"Consulta Externa\"}', '2026-07-24 01:39:26', '2026-07-24 01:39:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `patients`
--

CREATE TABLE `patients` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `platform_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_date` date DEFAULT NULL,
  `sex` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curp` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rfc` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `primary_doctor_id` bigint UNSIGNED DEFAULT NULL,
  `risk_level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'low',
  `enrolled_at` date DEFAULT NULL,
  `general_observations` text COLLATE utf8mb4_unicode_ci,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `profile_completed_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `patients`
--

INSERT INTO `patients` (`id`, `user_id`, `platform_number`, `first_name`, `last_name`, `full_name`, `birth_date`, `sex`, `curp`, `rfc`, `phone`, `email`, `address`, `status`, `primary_doctor_id`, `risk_level`, `enrolled_at`, `general_observations`, `email_verified_at`, `profile_completed_at`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 21, '100000001', 'Claudia Beatrizz', 'Salinas Vega', 'Claudia Beatrizz Salinas Vega', '1986-04-12', 'Femenino', NULL, 'SAVC860412AB1', '5555550101', 'paciente@demo.drsam.local', 'Av. Insurgentes Sur 100, Ciudad de Mexico', 'active', 1, 'high', '2026-01-15', 'Paciente demo para seguimiento cronico-degenerativo aseguradora.', '2026-07-24 01:40:33', '2026-07-24 01:40:33', '{\"review\": true, \"patient_location\": \"private\"}', NULL, 1, '2026-07-24 01:39:26', '2026-07-27 18:41:00', NULL),
(2, NULL, 'OP-PAC-1001', NULL, NULL, 'Claudia Beatriz Salinas Vega', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, 'low', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-28 03:31:22', '2026-07-28 03:31:22', NULL),
(3, NULL, 'OP-PAC-1002', NULL, NULL, 'Guillermo Guerrero', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, 'low', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-28 03:31:22', '2026-07-28 03:31:22', NULL),
(4, NULL, 'USR-HGCH-0001', 'Arturo', 'Hernandez', 'Arturo Hernandez', '1972-01-01', NULL, 'HEAA720314HMCRRR08', NULL, NULL, NULL, NULL, 'active', NULL, 'low', NULL, NULL, NULL, NULL, '{\"state\": \"México\", \"source\": \"outpatient_module\", \"nss_estatal\": \"MEX-248391\", \"nss_federal\": \"04967231458\"}', NULL, NULL, '2026-07-28 03:31:22', '2026-07-30 03:44:00', NULL),
(5, NULL, 'OP-PAC-1004', NULL, NULL, 'Javier Alejandro Hernandez', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, 'low', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-28 03:31:22', '2026-07-28 03:31:22', NULL),
(6, NULL, 'OP-PAC-1005', NULL, NULL, 'Maria Fernanda Lopez', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, 'low', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-28 03:31:22', '2026-07-28 03:31:22', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `patient_diagnoses`
--

CREATE TABLE `patient_diagnoses` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `chronic_condition_id` bigint UNSIGNED DEFAULT NULL,
  `condition_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `diagnosed_at` date DEFAULT NULL,
  `cie10` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doctor_id` bigint UNSIGNED DEFAULT NULL,
  `specialty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `indicated_treatment` text COLLATE utf8mb4_unicode_ci,
  `follow_up_frequency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `required_studies` text COLLATE utf8mb4_unicode_ci,
  `administrative_notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_surveillance',
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `patient_diagnoses`
--

INSERT INTO `patient_diagnoses` (`id`, `patient_id`, `chronic_condition_id`, `condition_name`, `diagnosed_at`, `cie10`, `doctor_id`, `specialty`, `indicated_treatment`, `follow_up_frequency`, `required_studies`, `administrative_notes`, `status`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 'Diabetes mellitus', '2024-03-10', 'E11', 1, 'Medicina interna', 'Control metabolico y apego farmacologico.', 'Mensual', 'Hemoglobina glucosilada, quimica sanguinea, EGO.', 'Requiere autorizacion para terapia continua.', 'in_surveillance', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `patient_orders`
--

CREATE TABLE `patient_orders` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED DEFAULT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'digital',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'created',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ordered_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `patient_orders`
--

INSERT INTO `patient_orders` (`id`, `patient_id`, `external_id`, `order_number`, `channel`, `status`, `subtotal`, `total`, `ordered_at`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'PED-DEMO-0001', 'digital', 'delivered', 352.00, 352.00, '2026-07-24 01:40:33', NULL, '2026-07-24 01:40:33', '2026-07-30 05:13:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `patient_order_items`
--

CREATE TABLE `patient_order_items` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_order_id` bigint UNSIGNED NOT NULL,
  `pharmacy_product_id` bigint UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int UNSIGNED NOT NULL DEFAULT '1',
  `unit_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `patient_order_items`
--

INSERT INTO `patient_order_items` (`id`, `patient_order_id`, `pharmacy_product_id`, `product_name`, `quantity`, `unit_price`, `total`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Paracetamol Tableta', 1, 352.00, 352.00, '{\"dispensations\": [{\"at\": \"2026-07-30T05:13:56.535755Z\", \"quantity\": 1, \"movements\": [{\"quantity\": 1, \"inventory_item_id\": 1}]}], \"filled_quantity\": 1}', '2026-07-24 01:40:33', '2026-07-30 05:13:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permissions`
--

INSERT INTO `permissions` (`id`, `key`, `name`, `description`, `module`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'insurance.view', 'Consultar modulo', NULL, 'insurance_health', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(2, 'insurance.manage_patients', 'Gestionar pacientes', NULL, 'insurance_health', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(3, 'insurance.manage_treatments', 'Gestionar diagnosticos y tratamientos', NULL, 'insurance_health', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(4, 'insurance.manage_deliveries', 'Gestionar entregas', NULL, 'insurance_health', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(5, 'insurance.manage_hospitalizations', 'Gestionar hospitalizaciones', NULL, 'insurance_health', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(6, 'insurance.manage_billing', 'Gestionar facturacion', NULL, 'insurance_health', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(7, 'insurance.manage_authorizations', 'Gestionar autorizaciones', NULL, 'insurance_health', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(8, 'insurance.manage_documents', 'Gestionar documentos', NULL, 'insurance_health', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(9, 'insurance.admin_users', 'Administrar usuarios y roles', NULL, 'insurance_health', NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permission_role`
--

CREATE TABLE `permission_role` (
  `role_id` bigint UNSIGNED NOT NULL,
  `permission_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permission_role`
--

INSERT INTO `permission_role` (`role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(1, 2, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(1, 3, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(1, 4, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(1, 5, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(1, 6, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(1, 7, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(1, 8, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(1, 9, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(2, 1, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(2, 3, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(2, 7, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(2, 8, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(3, 1, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(3, 2, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(3, 3, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(3, 8, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(4, 1, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(4, 4, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(4, 8, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(5, 1, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(5, 5, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(5, 7, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(5, 8, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(6, 1, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(6, 6, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(6, 8, '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(7, 1, '2026-07-24 01:40:33', '2026-07-24 01:40:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pharmacy_products`
--

CREATE TABLE `pharmacy_products` (
  `id` bigint UNSIGNED NOT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cnis` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `generic_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commercial_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dose` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dosage_form` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `presentation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `laboratory` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cofepris` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sanitary_registry` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requires_prescription` tinyint(1) NOT NULL DEFAULT '0',
  `controlled` tinyint(1) NOT NULL DEFAULT '0',
  `cold_chain` tinyint(1) NOT NULL DEFAULT '0',
  `sector_health` tinyint(1) NOT NULL DEFAULT '0',
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pharmacy_products`
--

INSERT INTO `pharmacy_products` (`id`, `external_id`, `cnis`, `name`, `generic_name`, `commercial_name`, `dose`, `unit`, `dosage_form`, `presentation`, `laboratory`, `supplier`, `barcode`, `cofepris`, `sanitary_registry`, `requires_prescription`, `controlled`, `cold_chain`, `sector_health`, `price`, `status`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 'imss-bienestar-010-000-0104-00', '010.000.0104.00', 'Paracetamol Tableta', 'Paracetamol', 'No especificada', '500', 'MG', 'Tableta', 'Envase con 10 tabletas', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 1, 352.00, 'active', NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pharmacy_warehouses`
--

CREATE TABLE `pharmacy_warehouses` (
  `id` bigint UNSIGNED NOT NULL,
  `medical_unit_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `responsible` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pharmacy_warehouses`
--

INSERT INTO `pharmacy_warehouses` (`id`, `medical_unit_id`, `name`, `type`, `responsible`, `status`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 'Almacen General de Farmacia Externa', 'general', 'Responsable de Farmacia', 'active', '{\"source\": \"demo\"}', '2026-07-30 05:22:01', '2026-07-30 05:33:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `platform_modules`
--

CREATE TABLE `platform_modules` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `roles` json DEFAULT NULL,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `platform_modules`
--

INSERT INTO `platform_modules` (`id`, `key`, `label`, `target`, `enabled`, `roles`, `settings`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', 'Modulo superadministrador', 'superadmin.dashboard', 1, '[\"superadmin\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(2, 'institution', 'Modulo institucion', 'institution.dashboard', 1, '[\"superadmin\", \"admin\", \"institution\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(3, 'unit', 'Modulo de unidad', 'unit.dashboard', 1, '[\"superadmin\", \"admin\", \"institution\", \"unit\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(4, 'operational', 'Area operativa', 'operational.dashboard', 1, '[\"superadmin\", \"admin\", \"institution\", \"unit\", \"operational\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(5, 'operational_outpatient', 'Area Operativa Consulta Externa', 'outpatient.dashboard', 1, '[\"superadmin\", \"admin\", \"institution\", \"unit\", \"operational\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(6, 'external_pharmacy', 'Area Operativa Farmacia', 'external-pharmacy.dashboard', 1, '[\"superadmin\", \"admin\", \"operational\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(7, 'digital_pharmacy', 'Farmacia Digital', 'pharmacy.dashboard', 1, '[\"superadmin\", \"admin\", \"operational\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(8, 'orders', 'Pedidos paciente', 'orders.index', 1, '[\"superadmin\", \"admin\", \"operational\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(9, 'insurance_health', 'Aseguradora salud', 'insurance.dashboard', 1, '[\"superadmin\", \"admin\", \"insurance_admin\", \"medical_auditor\", \"patient_coordinator\", \"delivery_coordinator\", \"hospital_coordinator\", \"billing\", \"read_only\", \"insurance_advisor\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(10, 'doctor', 'Modulo medico', 'doctor.dashboard', 1, '[\"superadmin\", \"admin\", \"doctor\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(11, 'patient', 'Modulo paciente', 'patient.dashboard', 1, '[\"superadmin\", \"admin\", \"patient\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(12, 'insurance_advisor', 'Asesor de seguros GMM', 'insurance-advisor.dashboard', 1, '[\"superadmin\", \"admin\", \"insurance_advisor\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(13, 'provider_npt', 'Proveedor NPT', 'provider.npt.dashboard', 1, '[\"superadmin\", \"admin\", \"provider\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(14, 'provider_chemo', 'Proveedor quimioterapias', 'provider.chemo.dashboard', 1, '[\"superadmin\", \"admin\", \"provider\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(15, 'provider_import', 'Proveedor importacion', 'proveedor-importacion.html', 1, '[\"superadmin\", \"admin\", \"provider\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(16, 'provider_medicines', 'Distribuidor de medicamentos', 'proveedor-medicamentos.html', 1, '[\"superadmin\", \"admin\", \"provider\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(17, 'provider_clinical_labs', 'Proveedor analisis clinicos', 'proveedor-analisis-clinicos.html', 1, '[\"superadmin\", \"admin\", \"provider\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(18, 'messenger', 'Mensajero', 'messenger.dashboard', 1, '[\"superadmin\", \"admin\", \"messenger\"]', '{\"legacy_bridge\": false}', '2026-07-24 01:40:33', '2026-07-24 01:40:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED DEFAULT NULL,
  `doctor_id` bigint UNSIGNED DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `issued_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `patient_id`, `doctor_id`, `code`, `status`, `issued_at`, `notes`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'RX-DEMO-0001', 'active', '2026-07-24 01:40:33', 'Receta demo para flujo paciente-farmacia.', NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prescription_items`
--

CREATE TABLE `prescription_items` (
  `id` bigint UNSIGNED NOT NULL,
  `prescription_id` bigint UNSIGNED NOT NULL,
  `medication_catalog_item_id` bigint UNSIGNED DEFAULT NULL,
  `medication_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dose` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `prescription_items`
--

INSERT INTO `prescription_items` (`id`, `prescription_id`, `medication_catalog_item_id`, `medication_name`, `dose`, `frequency`, `duration`, `instructions`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Paracetamol Tableta', '500 mg', 'Cada 8 horas', '3 dias', 'Tomar con alimentos.', NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `procedure_areas`
--

CREATE TABLE `procedure_areas` (
  `id` bigint UNSIGNED NOT NULL,
  `medical_unit_id` bigint UNSIGNED NOT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `floor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `simultaneous_capacity` int UNSIGNED NOT NULL DEFAULT '1',
  `responsible_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `procedure_areas`
--

INSERT INTO `procedure_areas` (`id`, `medical_unit_id`, `external_id`, `type`, `location`, `floor`, `unit_number`, `simultaneous_capacity`, `responsible_name`, `status`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 'b21f0540-5837-4511-b5dd-08bc833741c5', 'consulting', 'San Francisco 386', '34', 'C-01', 4, 'Responsable de Enfermeria', 'active', NULL, '2026-07-27 23:41:45', '2026-07-27 23:41:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `procedure_area_schedules`
--

CREATE TABLE `procedure_area_schedules` (
  `id` bigint UNSIGNED NOT NULL,
  `procedure_area_id` bigint UNSIGNED NOT NULL,
  `day_of_week` tinyint UNSIGNED NOT NULL,
  `starts_at` time NOT NULL,
  `ends_at` time NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `procedure_area_schedules`
--

INSERT INTO `procedure_area_schedules` (`id`, `procedure_area_id`, `day_of_week`, `starts_at`, `ends_at`, `active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '08:00:00', '16:00:00', 1, '2026-07-27 23:41:45', '2026-07-27 23:41:45'),
(2, 1, 2, '08:00:00', '16:00:00', 1, '2026-07-27 23:41:45', '2026-07-27 23:41:45'),
(3, 1, 3, '08:00:00', '16:00:00', 1, '2026-07-27 23:41:45', '2026-07-27 23:41:45'),
(4, 1, 4, '08:00:00', '16:00:00', 1, '2026-07-27 23:41:45', '2026-07-27 23:41:45'),
(5, 1, 5, '08:00:00', '16:00:00', 1, '2026-07-27 23:41:45', '2026-07-27 23:41:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `providers`
--

CREATE TABLE `providers` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `providers`
--

INSERT INTO `providers` (`id`, `user_id`, `name`, `provider_type`, `status`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 15, 'Proveedor NPT', 'npt', 'active', '{\"review\": true}', '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(2, 23, 'Proveedor Quimioterapias', 'chemotherapy', 'active', '{\"review\": true}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(3, 16, 'Proveedor Importacion de Medicamentos', 'import', 'active', '{\"review\": true}', '2026-07-24 01:40:33', '2026-07-24 01:40:33'),
(4, NULL, 'Proveedor operativo demo', 'npt', 'active', NULL, '2026-07-28 03:31:22', '2026-07-28 03:31:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `provider_requests`
--

CREATE TABLE `provider_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `provider_id` bigint UNSIGNED DEFAULT NULL,
  `patient_id` bigint UNSIGNED DEFAULT NULL,
  `medical_unit_id` bigint UNSIGNED DEFAULT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `requested_at` timestamp NULL DEFAULT NULL,
  `required_at` timestamp NULL DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `provider_requests`
--

INSERT INTO `provider_requests` (`id`, `provider_id`, `patient_id`, `medical_unit_id`, `external_id`, `request_type`, `status`, `requested_at`, `required_at`, `payload`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'npt-demo-0001', 'npt', 'cancelled', '2026-07-24 01:40:33', '2026-07-25 01:40:33', '{\"review\": true, \"source\": \"seeder\", \"service\": \"Nutricion parenteral\"}', '2026-07-24 01:40:33', '2026-07-28 03:24:59'),
(2, 4, 2, 1, 'OP-NPT-1001', 'npt', 'accepted', '2026-07-28 03:31:30', '2026-07-30 03:31:30', '{\"doctor\": \"Carter Jimmy\", \"volume\": 1200, \"service\": \"Medicina interna\", \"mix_status\": \"No Estable\", \"authorizations\": {\"pharmacy\": \"approved\", \"operational\": \"approved\"}, \"provider_assignment\": {\"name\": \"Prodifem\", \"selected_at\": \"2026-07-28T05:33:40.537352Z\", \"selected_by\": \"Responsable de Enfermeria\"}, \"sent_to_provider_at\": \"2026-07-28T05:33:40.537457Z\", \"authorization_history\": {\"pharmacy\": [{\"actor\": \"Responsable de Enfermeria\", \"notes\": null, \"status\": \"approved\", \"occurred_at\": \"2026-07-28T05:28:25.895075Z\"}], \"operational\": [{\"actor\": \"Responsable de Enfermeria\", \"notes\": null, \"status\": \"approved\", \"occurred_at\": \"2026-07-28T04:56:48.259566Z\"}]}, \"provider_dispatch_status\": \"sent-to-provider\", \"authorization_requirements\": [\"operational\", \"pharmacy\"]}', '2026-07-28 03:31:22', '2026-07-28 05:33:40'),
(3, 4, 3, 1, 'OP-NPT-1002', 'npt', 'requested', '2026-07-27 03:31:30', '2026-07-31 03:31:30', '{\"doctor\": \"Carter Jimmy\", \"volume\": 1000, \"service\": \"Cirugia general\", \"mix_status\": \"No Estable\", \"authorizations\": {\"pharmacy\": \"approved\", \"operational\": \"approved\"}, \"authorization_history\": {\"pharmacy\": [{\"actor\": \"Superadministrador\", \"notes\": null, \"status\": \"approved\", \"occurred_at\": \"2026-07-30T03:22:27.913678Z\"}]}, \"authorization_requirements\": [\"operational\", \"pharmacy\"]}', '2026-07-28 03:31:22', '2026-07-30 03:22:27'),
(4, 4, 4, 1, 'OP-NPT-1003', 'nutrition', 'accepted', '2026-07-26 03:31:30', '2026-08-01 03:31:30', '{\"doctor\": \"Carter Jimmy\", \"volume\": 1500, \"service\": \"Terapia intensiva\", \"mix_status\": \"No Estable\", \"authorizations\": {\"pharmacy\": \"approved\", \"operational\": \"approved\"}, \"authorization_requirements\": [\"operational\", \"pharmacy\"]}', '2026-07-28 03:31:22', '2026-07-28 05:21:35'),
(5, 4, 5, 1, 'OP-NPT-1004', 'import', 'requested', '2026-07-25 03:31:30', '2026-08-02 03:31:30', '{\"doctor\": \"Carter Jimmy\", \"volume\": 900, \"service\": \"Medicina interna\", \"mix_status\": \"No Estable\", \"authorizations\": {\"pharmacy\": \"pending\", \"operational\": \"rejected\"}, \"authorization_requirements\": [\"operational\", \"pharmacy\"]}', '2026-07-28 03:31:22', '2026-07-28 03:31:30'),
(6, 4, 6, 1, 'OP-NPT-1005', 'npt', 'rejected', '2026-07-24 03:31:30', '2026-08-03 03:31:30', '{\"doctor\": \"Carter Jimmy\", \"volume\": 1100, \"service\": \"Pediatria\", \"mix_status\": \"No Estable\", \"authorizations\": {\"pharmacy\": \"approved\", \"operational\": \"pending\"}, \"authorization_requirements\": [\"operational\", \"pharmacy\"]}', '2026-07-28 03:31:22', '2026-08-01 15:27:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `provider_request_status_events`
--

CREATE TABLE `provider_request_status_events` (
  `id` bigint UNSIGNED NOT NULL,
  `provider_request_id` bigint UNSIGNED NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `occurred_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `provider_request_status_events`
--

INSERT INTO `provider_request_status_events` (`id`, `provider_request_id`, `status`, `actor`, `notes`, `occurred_at`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 'cancelled', 'Operador Centro Oncologico', 'Cancelado desde tablero operativo', '2026-07-28 03:24:59', '{\"source\": \"operational_dashboard\", \"user_id\": 13}', '2026-07-28 03:24:59', '2026-07-28 03:24:59'),
(2, 2, 'requested', 'Datos demo', 'Solicitud de demostracion para area operativa.', '2026-07-28 03:31:30', '{\"source\": \"demo_seed\"}', '2026-07-28 03:31:22', '2026-07-28 03:31:30'),
(3, 3, 'requested', 'Datos demo', 'Solicitud de demostracion para area operativa.', '2026-07-27 03:31:30', '{\"source\": \"demo_seed\"}', '2026-07-28 03:31:22', '2026-07-28 03:31:30'),
(4, 4, 'requested', 'Datos demo', 'Solicitud de demostracion para area operativa.', '2026-07-26 03:31:30', '{\"source\": \"demo_seed\"}', '2026-07-28 03:31:22', '2026-07-28 03:31:30'),
(5, 5, 'requested', 'Datos demo', 'Solicitud de demostracion para area operativa.', '2026-07-25 03:31:30', '{\"source\": \"demo_seed\"}', '2026-07-28 03:31:22', '2026-07-28 03:31:30'),
(6, 6, 'requested', 'Datos demo', 'Solicitud de demostracion para area operativa.', '2026-07-24 03:31:30', '{\"source\": \"demo_seed\"}', '2026-07-28 03:31:22', '2026-07-28 03:31:30'),
(7, 2, 'authorization_approved', 'Responsable de Enfermeria', NULL, '2026-07-28 04:56:48', '{\"type\": \"authorization\", \"authorization\": \"operational\", \"request_status\": \"requested\", \"previous_status\": \"pending\"}', '2026-07-28 04:56:48', '2026-07-28 04:56:48'),
(8, 4, 'accepted', 'Responsable de Enfermeria', 'Enviado a proveedor desde tablero operativo', '2026-07-28 05:21:35', '{\"source\": \"operational_dashboard\", \"user_id\": 8}', '2026-07-28 05:21:35', '2026-07-28 05:21:35'),
(9, 4, 'accepted', 'Responsable de Enfermeria', 'Enviado a proveedor desde tablero operativo', '2026-07-28 05:21:39', '{\"source\": \"operational_dashboard\", \"user_id\": 8}', '2026-07-28 05:21:39', '2026-07-28 05:21:39'),
(10, 2, 'authorization_approved', 'Responsable de Enfermeria', NULL, '2026-07-28 05:28:25', '{\"type\": \"authorization\", \"authorization\": \"pharmacy\", \"request_status\": \"requested\", \"previous_status\": \"pending\"}', '2026-07-28 05:28:25', '2026-07-28 05:28:25'),
(11, 2, 'accepted', 'Responsable de Enfermeria', 'Enviado a Prodifem desde tablero operativo', '2026-07-28 05:33:40', '{\"source\": \"operational_dashboard\", \"user_id\": 8}', '2026-07-28 05:33:40', '2026-07-28 05:33:40'),
(12, 3, 'authorization_approved', 'Superadministrador', NULL, '2026-07-30 03:22:27', '{\"type\": \"authorization\", \"authorization\": \"pharmacy\", \"request_status\": \"requested\", \"previous_status\": \"pending\"}', '2026-07-30 03:22:27', '2026-07-30 03:22:27'),
(13, 6, 'rejected', 'Superadministrador', NULL, '2026-08-01 15:27:30', '{\"source\": \"provider_portal\", \"user_id\": 1, \"provider_type\": \"npt\"}', '2026-08-01 15:27:30', '2026-08-01 15:27:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '1',
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `key`, `name`, `description`, `module`, `is_system`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'insurance_admin', 'Administrador', 'Rol operativo del modulo aseguradora salud.', 'insurance_health', 1, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(2, 'medical_auditor', 'Medico auditor', 'Rol operativo del modulo aseguradora salud.', 'insurance_health', 1, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(3, 'patient_coordinator', 'Coordinador de pacientes', 'Rol operativo del modulo aseguradora salud.', 'insurance_health', 1, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(4, 'delivery_coordinator', 'Coordinador de entregas', 'Rol operativo del modulo aseguradora salud.', 'insurance_health', 1, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(5, 'hospital_coordinator', 'Coordinador hospitalario', 'Rol operativo del modulo aseguradora salud.', 'insurance_health', 1, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(6, 'billing', 'Facturacion', 'Rol operativo del modulo aseguradora salud.', 'insurance_health', 1, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL),
(7, 'read_only', 'Consulta solo lectura', 'Rol operativo del modulo aseguradora salud.', 'insurance_health', 1, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `services`
--

CREATE TABLE `services` (
  `id` bigint UNSIGNED NOT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `services`
--

INSERT INTO `services` (`id`, `external_id`, `category`, `specialty`, `name`, `code`, `status`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 'nutricion-parenteral', 'Farmaceuticos', 'Central de Mezclas de Nutricion Parenteral', 'Nutricion parenteral', NULL, 'active', NULL, '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(2, 'quimioterapias', 'Farmaceuticos', 'Central de Mezclas Oncologicas', 'Quimioterapias', NULL, 'active', NULL, '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(3, 'medicamentos-importacion', 'Farmaceuticos', 'Medicamentos de importacion', 'Importacion de medicamentos', NULL, 'active', NULL, '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(4, 'consulta-externa', 'Atencion medica', 'Consulta Externa', 'Consulta externa', NULL, 'active', NULL, '2026-07-24 01:39:26', '2026-07-24 01:39:26'),
(5, 'farmacia-digital', 'Farmacia', 'Farmacia Digital', 'Pedido y entrega de medicamentos', NULL, 'active', NULL, '2026-07-24 01:39:26', '2026-07-24 01:39:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `source_payloads`
--

CREATE TABLE `source_payloads` (
  `id` bigint UNSIGNED NOT NULL,
  `source_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json NOT NULL,
  `imported_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `treatments`
--

CREATE TABLE `treatments` (
  `id` bigint UNSIGNED NOT NULL,
  `patient_id` bigint UNSIGNED NOT NULL,
  `patient_diagnosis_id` bigint UNSIGNED DEFAULT NULL,
  `medication_id` bigint UNSIGNED DEFAULT NULL,
  `prescription_id` bigint UNSIGNED DEFAULT NULL,
  `prescribing_doctor_id` bigint UNSIGNED DEFAULT NULL,
  `medication_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active_substance` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `presentation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dose` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `starts_at` date DEFAULT NULL,
  `ends_at` date DEFAULT NULL,
  `requires_authorization` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `change_reason` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `treatments`
--

INSERT INTO `treatments` (`id`, `patient_id`, `patient_diagnosis_id`, `medication_id`, `prescription_id`, `prescribing_doctor_id`, `medication_name`, `active_substance`, `presentation`, `dose`, `frequency`, `duration`, `starts_at`, `ends_at`, `requires_authorization`, `status`, `change_reason`, `metadata`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 1, NULL, 1, 'Metformina', 'Metformina', 'Tableta 850 mg', '850 mg', 'Cada 12 horas', 'Tratamiento continuo', '2026-01-15', '2026-12-31', 1, 'active', NULL, NULL, NULL, NULL, '2026-07-24 01:40:33', '2026-07-24 01:40:33', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `treatment_change_logs`
--

CREATE TABLE `treatment_change_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `treatment_id` bigint UNSIGNED NOT NULL,
  `changed_by_user_id` bigint UNSIGNED DEFAULT NULL,
  `previous_payload` json DEFAULT NULL,
  `new_payload` json DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `changed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_demo` tinyint(1) NOT NULL DEFAULT '0',
  `passwordless_review` tinyint(1) NOT NULL DEFAULT '0',
  `metadata` json DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `email_verified_at`, `password`, `role`, `module`, `status`, `is_demo`, `passwordless_review`, `metadata`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Superadministrador', 'superadmin', 'superadmin@demo.drsam.local', '2026-07-24 01:40:28', '$2y$12$oU0hH7zT0EBZWwPjBJpv1uWFXTlD8uVLuCuFk11QcXZxIqsjq9Nwa', 'superadmin', 'superadmin', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:22', '2026-07-24 01:40:29'),
(2, 'Administracion General', 'admin', 'admin@demo.drsam.local', '2026-07-24 01:40:29', '$2y$12$/CIXIzGY.KumJN4tNg7vle8xByfjmwlCC2V0tIfRgVfQdYTOHPHTK', 'admin', 'institution', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:22', '2026-07-24 01:40:29'),
(3, 'Imss Bienestar Estado de Mexico', 'institucion', 'institucion@demo.drsam.local', '2026-07-24 01:40:29', '$2y$12$.w0z7uer3Tx6R4dBkI88IeVv.gSvzZJtxcze9Gq6uu2eYYZp58ST6', 'institution', 'institution', 'active', 1, 1, '{\"external_id\": \"inst-portal\"}', NULL, '2026-07-24 01:39:22', '2026-07-24 01:40:29'),
(4, 'Operadora de Hospitales Angeles', 'angeles', 'angeles@demo.drsam.local', '2026-07-24 01:40:29', '$2y$12$cN0uzOacs.8vA2auZm/g4uLq1zizRGpMC1A1TJ9pJmVaN5PRT/1YW', 'institution', 'institution', 'active', 1, 1, '{\"external_id\": \"inst-angeles\"}', NULL, '2026-07-24 01:39:22', '2026-07-24 01:40:29'),
(5, 'Imss Bienestar Ciudad de Mexico', 'imss.cdmx', 'imss.cdmx@demo.drsam.local', '2026-07-24 01:40:29', '$2y$12$./cPMIjjvxT56a2F2Gcbd.kjiwcgky3gewPXsEp.1WwJQjedAHXEi', 'institution', 'institution', 'active', 1, 1, '{\"external_id\": \"inst-imss-cdmx\"}', NULL, '2026-07-24 01:39:23', '2026-07-24 01:40:29'),
(6, 'IMSS Bienestar Estado de Mexico', 'imss.bienestar', 'imss.bienestar@demo.drsam.local', '2026-07-24 01:40:29', '$2y$12$T6gqjvTyvm4Dd7cLT/ZO9OUpgqZRmJWqiPrlLDHinRP4RjNoN1Qey', 'institution', 'institution', 'active', 1, 1, '{\"external_id\": \"inst-t2\"}', NULL, '2026-07-24 01:39:23', '2026-07-24 01:40:30'),
(7, 'Unidad medica demo', 'unidad.demo', 'unidad.demo@demo.drsam.local', '2026-07-24 01:40:30', '$2y$12$xmmuYajLiBrwZMI1G3MTD.6Cza4FCT0VuMKtKOmlQbDDNFDvU15se', 'unit', 'unit', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:23', '2026-07-24 01:40:30'),
(8, 'Responsable de Enfermeria', 'op.enfermeria', 'op.enfermeria@demo.drsam.local', '2026-07-24 01:40:30', '$2y$12$wGGcq78EErkrvnebVn3dn.EzglUEHIVJZGp7shCIqRc4zm9ghzueG', 'operational', 'operational', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:23', '2026-07-24 01:40:30'),
(9, 'Responsable de Farmacia intrahospitalaria', 'op.farmacia', 'op.farmacia@demo.drsam.local', '2026-07-24 01:40:30', '$2y$12$dC1lSTR/L3Jj31tzN3kKau27pbMfRw4a0mIk22/lYlwGTn2lR5hvO', 'operational', 'operational', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:23', '2026-07-24 01:40:30'),
(10, 'Responsable de Farmacia Externa', 'op.farmacia.externa', 'op.farmacia.externa@demo.drsam.local', '2026-07-24 01:40:30', '$2y$12$ZUJZ0GvHojY4TY2czPni7u7ONKyYJfDQzEnVOJLySO6qLvbe21NCW', 'operational', 'external_pharmacy', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:24', '2026-07-24 01:40:30'),
(11, 'Operacion Farmacia Digital', 'farmacia.digital', 'farmacia.digital@demo.drsam.local', '2026-07-24 01:40:30', '$2y$12$eONY7YxN01MSe9014kH.puIt.QTqCpGvMXYnYZ/GpcdJSf7es7bNG', 'operational', 'digital_pharmacy', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:24', '2026-07-24 01:40:31'),
(12, 'Aseguradora Salud', 'aseguradora.salud', 'aseguradora.salud@demo.drsam.local', '2026-07-24 01:40:31', '$2y$12$2q36uBVx7GI4JGzRsrqsxe07cpMgS1ZromPMmouQlQhoMXCQSTwGu', 'insurance_admin', 'insurance_health', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:24', '2026-07-24 01:40:31'),
(13, 'Operador Centro Oncologico', 'op.oncologia', 'op.oncologia@demo.drsam.local', '2026-07-24 01:40:31', '$2y$12$qudkVfGyg9wsamBr9Hd53elhNo/VqJng2S5Thb/i1q7iVLZavoN32', 'operational', 'operational', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:24', '2026-07-24 01:40:31'),
(14, 'Operador Consulta Externa', 'op.consulta', 'op.consulta@demo.drsam.local', '2026-07-24 01:40:31', '$2y$12$KI3ubzSPVMCce9l2RMBM3Ox6E0zIqQgYfZI1ctgqUwyg2vvsKpOGC', 'operational', 'operational_outpatient', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:24', '2026-07-24 01:40:31'),
(15, 'Proveedor NPT', 'proveedor', 'proveedor@demo.drsam.local', '2026-07-24 01:40:31', '$2y$12$y8VVOzrm5uCDmT/4LZDXbeq77fDDC3.5tSbZQqOy8DC8vwTrvElgW', 'provider', 'provider_npt', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:24', '2026-07-24 01:40:31'),
(16, 'Proveedor Importacion', 'proveedor.importacion', 'proveedor.importacion@demo.drsam.local', '2026-07-24 01:40:31', '$2y$12$GN7/SpwAqE7Km2Vm4rYnIOqgt79NWDjKKy552r2RbqsFaau5nBDz6', 'provider', 'provider_import', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:25', '2026-07-24 01:40:32'),
(17, 'Distribuidor de Medicamentos', 'proveedor.medicamentos', 'proveedor.medicamentos@demo.drsam.local', '2026-07-24 01:40:32', '$2y$12$SdAhc9SbOIbi7YJSGqGf2O2.6I8cU4hdkOq5WYpcfKnW2Dl61gMR2', 'provider', 'provider_medicines', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:25', '2026-07-24 01:40:32'),
(18, 'Proveedor Analisis Clinicos', 'proveedor.analisis', 'proveedor.analisis@demo.drsam.local', '2026-07-24 01:40:32', '$2y$12$O2xQxaCJMRS2G9AHZfqBHeT.iN29JnyawuMr.mq4aLZn/EOfCot2q', 'provider', 'provider_clinical_labs', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:25', '2026-07-24 01:40:32'),
(19, 'Luis Hernandez', 'mensajero', 'mensajero@demo.drsam.local', '2026-07-24 01:40:32', '$2y$12$RWQFXSljLF8TooYPJc4nnOUX21UlSstBD2xwSviG20Rv8pMRGvUIm', 'messenger', 'messenger', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:25', '2026-07-24 01:40:32'),
(20, 'Dr. Carter Jimmy', 'jimmy.carter', 'jimmy.carter@demo.drsam.local', '2026-07-24 01:40:32', '$2y$12$pNFs3cYlqaqz.5eO3yY3f.7LBUsSqLrv4HROQ5lHpTAzHR3LmBnXq', 'doctor', 'doctor', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:25', '2026-07-24 01:40:32'),
(21, 'Claudia Beatriz Salinas Vega', 'paciente', 'paciente@demo.drsam.local', '2026-07-24 01:40:32', '$2y$12$QdRd4uYWmRqzdVs8T7/IsOvmAg4RDviWqfbbQkCsFHjWTIPM5K97W', 'patient', 'patient', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:26', '2026-07-24 01:40:33'),
(22, 'Asesor Seguros GMM', 'asesor.seguros', 'asesor.seguros@demo.drsam.local', '2026-07-24 01:40:33', '$2y$12$DqbqKAkltXcn51wvi5knO.DAYWtRx4I9Nu4NL7vW.VVNi1zuSslrG', 'insurance_advisor', 'insurance_advisor', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:39:26', '2026-07-24 01:40:33'),
(23, 'Proveedor Quimioterapias', 'proveedor.quimioterapias', 'proveedor.quimioterapias@demo.drsam.local', '2026-07-24 01:40:31', '$2y$12$c6aE5SMaLvPU3KbBiwJyFeIfesDX6zwC8dWVCDxsKMUnSieT3yMTy', 'provider', 'provider_chemo', 'active', 1, 1, '{\"external_id\": null}', NULL, '2026-07-24 01:40:31', '2026-07-24 01:40:31');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointments_patient_id_foreign` (`patient_id`),
  ADD KEY `appointments_doctor_id_foreign` (`doctor_id`),
  ADD KEY `appointments_medical_unit_id_foreign` (`medical_unit_id`),
  ADD KEY `appointments_specialty_index` (`specialty`),
  ADD KEY `appointments_status_index` (`status`),
  ADD KEY `appointments_starts_at_index` (`starts_at`),
  ADD KEY `appointments_procedure_area_id_foreign` (`procedure_area_id`);

--
-- Indices de la tabla `appointment_status_events`
--
ALTER TABLE `appointment_status_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_status_events_appointment_id_foreign` (`appointment_id`),
  ADD KEY `appointment_status_events_changed_by_foreign` (`changed_by`),
  ADD KEY `appointment_status_events_from_status_index` (`from_status`),
  ADD KEY `appointment_status_events_to_status_index` (`to_status`);

--
-- Indices de la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_foreign` (`user_id`),
  ADD KEY `audit_logs_event_index` (`event`),
  ADD KEY `audit_logs_auditable_type_index` (`auditable_type`),
  ADD KEY `audit_logs_auditable_id_index` (`auditable_id`);

--
-- Indices de la tabla `authorizations`
--
ALTER TABLE `authorizations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `authorizations_patient_id_foreign` (`patient_id`),
  ADD KEY `authorizations_treatment_id_foreign` (`treatment_id`),
  ADD KEY `authorizations_hospitalization_id_foreign` (`hospitalization_id`),
  ADD KEY `authorizations_created_by_foreign` (`created_by`),
  ADD KEY `authorizations_updated_by_foreign` (`updated_by`),
  ADD KEY `authorizations_type_index` (`type`),
  ADD KEY `authorizations_requested_at_index` (`requested_at`),
  ADD KEY `authorizations_status_index` (`status`),
  ADD KEY `authorizations_authorization_number_index` (`authorization_number`),
  ADD KEY `authorizations_valid_until_index` (`valid_until`);

--
-- Indices de la tabla `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indices de la tabla `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indices de la tabla `chronic_conditions`
--
ALTER TABLE `chronic_conditions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chronic_conditions_name_unique` (`name`),
  ADD KEY `chronic_conditions_created_by_foreign` (`created_by`),
  ADD KEY `chronic_conditions_updated_by_foreign` (`updated_by`),
  ADD KEY `chronic_conditions_default_cie10_index` (`default_cie10`),
  ADD KEY `chronic_conditions_status_index` (`status`);

--
-- Indices de la tabla `clinical_encounters`
--
ALTER TABLE `clinical_encounters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clinical_encounters_appointment_id_foreign` (`appointment_id`),
  ADD KEY `clinical_encounters_patient_id_foreign` (`patient_id`),
  ADD KEY `clinical_encounters_medical_unit_id_foreign` (`medical_unit_id`),
  ADD KEY `clinical_encounters_procedure_area_id_foreign` (`procedure_area_id`),
  ADD KEY `clinical_encounters_doctor_id_status_index` (`doctor_id`,`status`);

--
-- Indices de la tabla `clinical_records`
--
ALTER TABLE `clinical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clinical_records_patient_id_foreign` (`patient_id`),
  ADD KEY `clinical_records_doctor_id_foreign` (`doctor_id`),
  ADD KEY `clinical_records_record_type_index` (`record_type`),
  ADD KEY `clinical_records_recorded_at_index` (`recorded_at`);

--
-- Indices de la tabla `contracted_services`
--
ALTER TABLE `contracted_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contracted_services_institution_id_foreign` (`institution_id`),
  ADD KEY `contracted_services_medical_unit_id_foreign` (`medical_unit_id`),
  ADD KEY `contracted_services_service_id_foreign` (`service_id`),
  ADD KEY `contracted_services_status_index` (`status`);

--
-- Indices de la tabla `delivery_reports`
--
ALTER TABLE `delivery_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_reports_delivery_route_id_foreign` (`delivery_route_id`),
  ADD KEY `delivery_reports_status_index` (`status`),
  ADD KEY `delivery_reports_reported_at_index` (`reported_at`);

--
-- Indices de la tabla `delivery_routes`
--
ALTER TABLE `delivery_routes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_routes_route_code_unique` (`route_code`),
  ADD KEY `delivery_routes_messenger_profile_id_foreign` (`messenger_profile_id`),
  ADD KEY `delivery_routes_provider_request_id_foreign` (`provider_request_id`),
  ADD KEY `delivery_routes_patient_order_id_foreign` (`patient_order_id`),
  ADD KEY `delivery_routes_status_index` (`status`),
  ADD KEY `delivery_routes_scheduled_at_index` (`scheduled_at`);

--
-- Indices de la tabla `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doctors_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `doctors_legacy_id_unique` (`external_id`),
  ADD KEY `doctors_medical_unit_id_foreign` (`medical_unit_id`),
  ADD KEY `doctors_professional_license_index` (`professional_license`),
  ADD KEY `doctors_specialty_index` (`specialty`),
  ADD KEY `doctors_status_index` (`status`);

--
-- Indices de la tabla `doctor_availability_exceptions`
--
ALTER TABLE `doctor_availability_exceptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_availability_exceptions_doctor_id_foreign` (`doctor_id`),
  ADD KEY `doctor_availability_exceptions_doctor_clinic_id_foreign` (`doctor_clinic_id`);

--
-- Indices de la tabla `doctor_availability_rules`
--
ALTER TABLE `doctor_availability_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_availability_rules_doctor_clinic_id_foreign` (`doctor_clinic_id`),
  ADD KEY `doctor_availability_rules_doctor_id_weekday_status_index` (`doctor_id`,`weekday`,`status`);

--
-- Indices de la tabla `doctor_clinics`
--
ALTER TABLE `doctor_clinics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_clinics_doctor_id_foreign` (`doctor_id`),
  ADD KEY `doctor_clinics_medical_unit_id_foreign` (`medical_unit_id`);

--
-- Indices de la tabla `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documents_patient_id_foreign` (`patient_id`),
  ADD KEY `documents_treatment_id_foreign` (`treatment_id`),
  ADD KEY `documents_medication_delivery_id_foreign` (`medication_delivery_id`),
  ADD KEY `documents_hospitalization_id_foreign` (`hospitalization_id`),
  ADD KEY `documents_authorization_id_foreign` (`authorization_id`),
  ADD KEY `documents_invoice_id_foreign` (`invoice_id`),
  ADD KEY `documents_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `documents_created_by_foreign` (`created_by`),
  ADD KEY `documents_updated_by_foreign` (`updated_by`),
  ADD KEY `documents_document_type_index` (`document_type`),
  ADD KEY `documents_loaded_at_index` (`loaded_at`),
  ADD KEY `documents_expires_at_index` (`expires_at`),
  ADD KEY `documents_status_index` (`status`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indices de la tabla `hospitalizations`
--
ALTER TABLE `hospitalizations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hospitalizations_patient_id_foreign` (`patient_id`),
  ADD KEY `hospitalizations_hospital_id_foreign` (`hospital_id`),
  ADD KEY `hospitalizations_doctor_id_foreign` (`doctor_id`),
  ADD KEY `hospitalizations_created_by_foreign` (`created_by`),
  ADD KEY `hospitalizations_updated_by_foreign` (`updated_by`),
  ADD KEY `hospitalizations_hospital_name_index` (`hospital_name`),
  ADD KEY `hospitalizations_admitted_at_index` (`admitted_at`),
  ADD KEY `hospitalizations_discharged_at_index` (`discharged_at`),
  ADD KEY `hospitalizations_area_index` (`area`),
  ADD KEY `hospitalizations_event_type_index` (`event_type`),
  ADD KEY `hospitalizations_authorization_number_index` (`authorization_number`),
  ADD KEY `hospitalizations_status_index` (`status`);

--
-- Indices de la tabla `hospitalization_daily_notes`
--
ALTER TABLE `hospitalization_daily_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hospitalization_daily_notes_hospitalization_id_foreign` (`hospitalization_id`),
  ADD KEY `hospitalization_daily_notes_captured_by_foreign` (`captured_by`),
  ADD KEY `hospitalization_daily_notes_created_by_foreign` (`created_by`),
  ADD KEY `hospitalization_daily_notes_updated_by_foreign` (`updated_by`),
  ADD KEY `hospitalization_daily_notes_note_date_index` (`note_date`),
  ADD KEY `hospitalization_daily_notes_general_clinical_status_index` (`general_clinical_status`),
  ADD KEY `hospitalization_daily_notes_prolonged_stay_risk_index` (`prolonged_stay_risk`);

--
-- Indices de la tabla `hospitalization_procedures`
--
ALTER TABLE `hospitalization_procedures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hospitalization_procedures_hospitalization_id_foreign` (`hospitalization_id`),
  ADD KEY `hospitalization_procedures_doctor_id_foreign` (`doctor_id`),
  ADD KEY `hospitalization_procedures_created_by_foreign` (`created_by`),
  ADD KEY `hospitalization_procedures_updated_by_foreign` (`updated_by`),
  ADD KEY `hospitalization_procedures_procedure_name_index` (`procedure_name`),
  ADD KEY `hospitalization_procedures_performed_at_index` (`performed_at`);

--
-- Indices de la tabla `hospitals`
--
ALTER TABLE `hospitals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hospitals_medical_unit_id_foreign` (`medical_unit_id`),
  ADD KEY `hospitals_provider_id_foreign` (`provider_id`),
  ADD KEY `hospitals_created_by_foreign` (`created_by`),
  ADD KEY `hospitals_updated_by_foreign` (`updated_by`),
  ADD KEY `hospitals_name_index` (`name`),
  ADD KEY `hospitals_rfc_index` (`rfc`),
  ADD KEY `hospitals_network_type_index` (`network_type`),
  ADD KEY `hospitals_status_index` (`status`);

--
-- Indices de la tabla `institutions`
--
ALTER TABLE `institutions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `institutions_legacy_id_unique` (`external_id`),
  ADD KEY `institutions_owner_user_id_foreign` (`owner_user_id`),
  ADD KEY `institutions_status_index` (`status`);

--
-- Indices de la tabla `insurance_advisor_notifications`
--
ALTER TABLE `insurance_advisor_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `insurance_advisor_notifications_code_unique` (`code`),
  ADD KEY `insurance_advisor_notifications_insurance_policy_id_foreign` (`insurance_policy_id`),
  ADD KEY `insurance_advisor_notifications_type_index` (`type`),
  ADD KEY `insurance_advisor_notifications_audience_index` (`audience`),
  ADD KEY `insurance_advisor_notifications_occurred_at_index` (`occurred_at`);

--
-- Indices de la tabla `insurance_carriers`
--
ALTER TABLE `insurance_carriers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `insurance_carriers_slug_unique` (`slug`),
  ADD KEY `insurance_carriers_status_index` (`status`);

--
-- Indices de la tabla `insurance_policies`
--
ALTER TABLE `insurance_policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `insurance_policies_policy_number_unique` (`policy_number`),
  ADD KEY `insurance_policies_patient_id_foreign` (`patient_id`),
  ADD KEY `insurance_policies_created_by_foreign` (`created_by`),
  ADD KEY `insurance_policies_updated_by_foreign` (`updated_by`),
  ADD KEY `insurance_policies_insurer_name_index` (`insurer_name`),
  ADD KEY `insurance_policies_plan_name_index` (`plan_name`),
  ADD KEY `insurance_policies_employer_name_index` (`employer_name`),
  ADD KEY `insurance_policies_status_index` (`status`);

--
-- Indices de la tabla `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_items_pharmacy_product_id_foreign` (`pharmacy_product_id`),
  ADD KEY `inventory_items_medical_unit_id_foreign` (`medical_unit_id`),
  ADD KEY `inventory_items_warehouse_index` (`warehouse`),
  ADD KEY `inventory_items_lot_index` (`lot`),
  ADD KEY `inventory_items_status_index` (`status`);

--
-- Indices de la tabla `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_movements_inventory_item_id_foreign` (`inventory_item_id`),
  ADD KEY `inventory_movements_medical_unit_id_foreign` (`medical_unit_id`),
  ADD KEY `inventory_movements_user_id_foreign` (`user_id`),
  ADD KEY `inventory_movements_type_index` (`type`);

--
-- Indices de la tabla `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoices_fiscal_uuid_unique` (`fiscal_uuid`),
  ADD KEY `invoices_hospitalization_id_foreign` (`hospitalization_id`),
  ADD KEY `invoices_hospital_id_foreign` (`hospital_id`),
  ADD KEY `invoices_provider_id_foreign` (`provider_id`),
  ADD KEY `invoices_created_by_foreign` (`created_by`),
  ADD KEY `invoices_updated_by_foreign` (`updated_by`),
  ADD KEY `invoices_provider_name_index` (`provider_name`),
  ADD KEY `invoices_provider_rfc_index` (`provider_rfc`),
  ADD KEY `invoices_invoice_number_index` (`invoice_number`),
  ADD KEY `invoices_invoice_date_index` (`invoice_date`),
  ADD KEY `invoices_status_index` (`status`),
  ADD KEY `invoices_paid_at_index` (`paid_at`);

--
-- Indices de la tabla `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  ADD KEY `invoice_items_created_by_foreign` (`created_by`),
  ADD KEY `invoice_items_updated_by_foreign` (`updated_by`),
  ADD KEY `invoice_items_concept_type_index` (`concept_type`);

--
-- Indices de la tabla `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indices de la tabla `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `medical_devices`
--
ALTER TABLE `medical_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medical_devices_code_unique` (`code`),
  ADD KEY `medical_devices_status_index` (`status`);

--
-- Indices de la tabla `medical_units`
--
ALTER TABLE `medical_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medical_units_legacy_id_unique` (`external_id`),
  ADD KEY `medical_units_institution_id_foreign` (`institution_id`),
  ADD KEY `medical_units_code_index` (`code`),
  ADD KEY `medical_units_clues_index` (`clues`),
  ADD KEY `medical_units_municipality_index` (`municipality`),
  ADD KEY `medical_units_state_index` (`state`),
  ADD KEY `medical_units_status_index` (`status`);

--
-- Indices de la tabla `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medications_pharmacy_product_id_foreign` (`pharmacy_product_id`),
  ADD KEY `medications_medication_catalog_item_id_foreign` (`medication_catalog_item_id`),
  ADD KEY `medications_created_by_foreign` (`created_by`),
  ADD KEY `medications_updated_by_foreign` (`updated_by`),
  ADD KEY `medications_name_index` (`name`),
  ADD KEY `medications_active_substance_index` (`active_substance`),
  ADD KEY `medications_status_index` (`status`);

--
-- Indices de la tabla `medication_catalog_items`
--
ALTER TABLE `medication_catalog_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medication_catalog_items_legacy_id_unique` (`external_id`),
  ADD KEY `medication_catalog_items_institution_id_foreign` (`institution_id`),
  ADD KEY `medication_catalog_items_cnis_index` (`cnis`),
  ADD KEY `medication_catalog_items_name_index` (`name`),
  ADD KEY `medication_catalog_items_generic_name_index` (`generic_name`),
  ADD KEY `medication_catalog_items_status_index` (`status`);

--
-- Indices de la tabla `medication_deliveries`
--
ALTER TABLE `medication_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medication_deliveries_patient_id_foreign` (`patient_id`),
  ADD KEY `medication_deliveries_treatment_id_foreign` (`treatment_id`),
  ADD KEY `medication_deliveries_medication_id_foreign` (`medication_id`),
  ADD KEY `medication_deliveries_provider_id_foreign` (`provider_id`),
  ADD KEY `medication_deliveries_created_by_foreign` (`created_by`),
  ADD KEY `medication_deliveries_updated_by_foreign` (`updated_by`),
  ADD KEY `medication_deliveries_scheduled_delivery_date_index` (`scheduled_delivery_date`),
  ADD KEY `medication_deliveries_actual_delivery_date_index` (`actual_delivery_date`),
  ADD KEY `medication_deliveries_status_index` (`status`);

--
-- Indices de la tabla `messenger_profiles`
--
ALTER TABLE `messenger_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `messenger_profiles_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `messenger_profiles_legacy_id_unique` (`external_id`),
  ADD KEY `messenger_profiles_status_index` (`status`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `operational_areas`
--
ALTER TABLE `operational_areas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `operational_areas_key_unique` (`key`);

--
-- Indices de la tabla `operational_profiles`
--
ALTER TABLE `operational_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `operational_profiles_user_id_foreign` (`user_id`),
  ADD KEY `operational_profiles_medical_unit_id_foreign` (`medical_unit_id`),
  ADD KEY `operational_profiles_operational_area_id_foreign` (`operational_area_id`),
  ADD KEY `operational_profiles_status_index` (`status`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patients_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `patients_platform_number_unique` (`platform_number`),
  ADD UNIQUE KEY `patients_curp_unique` (`curp`),
  ADD KEY `patients_email_index` (`email`),
  ADD KEY `patients_status_index` (`status`),
  ADD KEY `patients_primary_doctor_id_foreign` (`primary_doctor_id`),
  ADD KEY `patients_created_by_foreign` (`created_by`),
  ADD KEY `patients_updated_by_foreign` (`updated_by`),
  ADD KEY `patients_rfc_index` (`rfc`),
  ADD KEY `patients_risk_level_index` (`risk_level`);

--
-- Indices de la tabla `patient_diagnoses`
--
ALTER TABLE `patient_diagnoses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_diagnoses_patient_id_foreign` (`patient_id`),
  ADD KEY `patient_diagnoses_chronic_condition_id_foreign` (`chronic_condition_id`),
  ADD KEY `patient_diagnoses_doctor_id_foreign` (`doctor_id`),
  ADD KEY `patient_diagnoses_created_by_foreign` (`created_by`),
  ADD KEY `patient_diagnoses_updated_by_foreign` (`updated_by`),
  ADD KEY `patient_diagnoses_condition_name_index` (`condition_name`),
  ADD KEY `patient_diagnoses_diagnosed_at_index` (`diagnosed_at`),
  ADD KEY `patient_diagnoses_cie10_index` (`cie10`),
  ADD KEY `patient_diagnoses_specialty_index` (`specialty`),
  ADD KEY `patient_diagnoses_status_index` (`status`);

--
-- Indices de la tabla `patient_orders`
--
ALTER TABLE `patient_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_orders_legacy_id_unique` (`external_id`),
  ADD UNIQUE KEY `patient_orders_order_number_unique` (`order_number`),
  ADD KEY `patient_orders_patient_id_foreign` (`patient_id`),
  ADD KEY `patient_orders_status_index` (`status`),
  ADD KEY `patient_orders_ordered_at_index` (`ordered_at`);

--
-- Indices de la tabla `patient_order_items`
--
ALTER TABLE `patient_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_order_items_patient_order_id_foreign` (`patient_order_id`),
  ADD KEY `patient_order_items_pharmacy_product_id_foreign` (`pharmacy_product_id`);

--
-- Indices de la tabla `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_key_unique` (`key`),
  ADD KEY `permissions_created_by_foreign` (`created_by`),
  ADD KEY `permissions_updated_by_foreign` (`updated_by`),
  ADD KEY `permissions_module_index` (`module`);

--
-- Indices de la tabla `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_role_permission_id_foreign` (`permission_id`);

--
-- Indices de la tabla `pharmacy_products`
--
ALTER TABLE `pharmacy_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pharmacy_products_legacy_id_unique` (`external_id`),
  ADD KEY `pharmacy_products_cnis_index` (`cnis`),
  ADD KEY `pharmacy_products_name_index` (`name`),
  ADD KEY `pharmacy_products_generic_name_index` (`generic_name`),
  ADD KEY `pharmacy_products_barcode_index` (`barcode`),
  ADD KEY `pharmacy_products_status_index` (`status`);

--
-- Indices de la tabla `pharmacy_warehouses`
--
ALTER TABLE `pharmacy_warehouses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pharmacy_warehouses_medical_unit_id_name_unique` (`medical_unit_id`,`name`),
  ADD KEY `pharmacy_warehouses_status_index` (`status`);

--
-- Indices de la tabla `platform_modules`
--
ALTER TABLE `platform_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `platform_modules_key_unique` (`key`),
  ADD KEY `platform_modules_enabled_index` (`enabled`);

--
-- Indices de la tabla `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prescriptions_code_unique` (`code`),
  ADD KEY `prescriptions_patient_id_foreign` (`patient_id`),
  ADD KEY `prescriptions_doctor_id_foreign` (`doctor_id`),
  ADD KEY `prescriptions_status_index` (`status`),
  ADD KEY `prescriptions_issued_at_index` (`issued_at`);

--
-- Indices de la tabla `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_items_prescription_id_foreign` (`prescription_id`),
  ADD KEY `prescription_items_medication_catalog_item_id_index` (`medication_catalog_item_id`);

--
-- Indices de la tabla `procedure_areas`
--
ALTER TABLE `procedure_areas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `procedure_areas_medical_unit_id_type_unit_number_unique` (`medical_unit_id`,`type`,`unit_number`),
  ADD KEY `procedure_areas_legacy_id_index` (`external_id`),
  ADD KEY `procedure_areas_type_index` (`type`),
  ADD KEY `procedure_areas_status_index` (`status`);

--
-- Indices de la tabla `procedure_area_schedules`
--
ALTER TABLE `procedure_area_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `procedure_area_schedule_unique` (`procedure_area_id`,`day_of_week`,`starts_at`,`ends_at`);

--
-- Indices de la tabla `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `providers_user_id_unique` (`user_id`),
  ADD KEY `providers_provider_type_index` (`provider_type`),
  ADD KEY `providers_status_index` (`status`);

--
-- Indices de la tabla `provider_requests`
--
ALTER TABLE `provider_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `provider_requests_legacy_id_unique` (`external_id`),
  ADD KEY `provider_requests_provider_id_foreign` (`provider_id`),
  ADD KEY `provider_requests_patient_id_foreign` (`patient_id`),
  ADD KEY `provider_requests_medical_unit_id_foreign` (`medical_unit_id`),
  ADD KEY `provider_requests_request_type_index` (`request_type`),
  ADD KEY `provider_requests_status_index` (`status`),
  ADD KEY `provider_requests_requested_at_index` (`requested_at`);

--
-- Indices de la tabla `provider_request_status_events`
--
ALTER TABLE `provider_request_status_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_request_status_events_provider_request_id_foreign` (`provider_request_id`),
  ADD KEY `provider_request_status_events_status_index` (`status`),
  ADD KEY `provider_request_status_events_occurred_at_index` (`occurred_at`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_key_unique` (`key`),
  ADD KEY `roles_created_by_foreign` (`created_by`),
  ADD KEY `roles_updated_by_foreign` (`updated_by`),
  ADD KEY `roles_module_index` (`module`);

--
-- Indices de la tabla `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `services_legacy_id_unique` (`external_id`),
  ADD KEY `services_category_index` (`category`),
  ADD KEY `services_specialty_index` (`specialty`),
  ADD KEY `services_code_index` (`code`),
  ADD KEY `services_status_index` (`status`);

--
-- Indices de la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indices de la tabla `source_payloads`
--
ALTER TABLE `source_payloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `legacy_payloads_source_key_index` (`source_key`),
  ADD KEY `legacy_payloads_legacy_id_index` (`external_id`),
  ADD KEY `legacy_payloads_entity_type_index` (`entity_type`),
  ADD KEY `legacy_payloads_imported_at_index` (`imported_at`);

--
-- Indices de la tabla `treatments`
--
ALTER TABLE `treatments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `treatments_patient_id_foreign` (`patient_id`),
  ADD KEY `treatments_patient_diagnosis_id_foreign` (`patient_diagnosis_id`),
  ADD KEY `treatments_medication_id_foreign` (`medication_id`),
  ADD KEY `treatments_prescription_id_foreign` (`prescription_id`),
  ADD KEY `treatments_prescribing_doctor_id_foreign` (`prescribing_doctor_id`),
  ADD KEY `treatments_created_by_foreign` (`created_by`),
  ADD KEY `treatments_updated_by_foreign` (`updated_by`),
  ADD KEY `treatments_medication_name_index` (`medication_name`),
  ADD KEY `treatments_active_substance_index` (`active_substance`),
  ADD KEY `treatments_starts_at_index` (`starts_at`),
  ADD KEY `treatments_ends_at_index` (`ends_at`),
  ADD KEY `treatments_status_index` (`status`);

--
-- Indices de la tabla `treatment_change_logs`
--
ALTER TABLE `treatment_change_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `treatment_change_logs_treatment_id_foreign` (`treatment_id`),
  ADD KEY `treatment_change_logs_changed_by_user_id_foreign` (`changed_by_user_id`),
  ADD KEY `treatment_change_logs_changed_at_index` (`changed_at`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_index` (`role`),
  ADD KEY `users_module_index` (`module`),
  ADD KEY `users_status_index` (`status`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `appointment_status_events`
--
ALTER TABLE `appointment_status_events`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `authorizations`
--
ALTER TABLE `authorizations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `chronic_conditions`
--
ALTER TABLE `chronic_conditions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `clinical_encounters`
--
ALTER TABLE `clinical_encounters`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clinical_records`
--
ALTER TABLE `clinical_records`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `contracted_services`
--
ALTER TABLE `contracted_services`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `delivery_reports`
--
ALTER TABLE `delivery_reports`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `delivery_routes`
--
ALTER TABLE `delivery_routes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `doctor_availability_exceptions`
--
ALTER TABLE `doctor_availability_exceptions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `doctor_availability_rules`
--
ALTER TABLE `doctor_availability_rules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `doctor_clinics`
--
ALTER TABLE `doctor_clinics`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `documents`
--
ALTER TABLE `documents`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `hospitalizations`
--
ALTER TABLE `hospitalizations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `hospitalization_daily_notes`
--
ALTER TABLE `hospitalization_daily_notes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `hospitalization_procedures`
--
ALTER TABLE `hospitalization_procedures`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `hospitals`
--
ALTER TABLE `hospitals`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `institutions`
--
ALTER TABLE `institutions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `insurance_advisor_notifications`
--
ALTER TABLE `insurance_advisor_notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `insurance_carriers`
--
ALTER TABLE `insurance_carriers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `insurance_policies`
--
ALTER TABLE `insurance_policies`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `medical_devices`
--
ALTER TABLE `medical_devices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `medical_units`
--
ALTER TABLE `medical_units`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `medications`
--
ALTER TABLE `medications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `medication_catalog_items`
--
ALTER TABLE `medication_catalog_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `medication_deliveries`
--
ALTER TABLE `medication_deliveries`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `messenger_profiles`
--
ALTER TABLE `messenger_profiles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `operational_areas`
--
ALTER TABLE `operational_areas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `operational_profiles`
--
ALTER TABLE `operational_profiles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `patients`
--
ALTER TABLE `patients`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `patient_diagnoses`
--
ALTER TABLE `patient_diagnoses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `patient_orders`
--
ALTER TABLE `patient_orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `patient_order_items`
--
ALTER TABLE `patient_order_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `pharmacy_products`
--
ALTER TABLE `pharmacy_products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pharmacy_warehouses`
--
ALTER TABLE `pharmacy_warehouses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `platform_modules`
--
ALTER TABLE `platform_modules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `prescription_items`
--
ALTER TABLE `prescription_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `procedure_areas`
--
ALTER TABLE `procedure_areas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `procedure_area_schedules`
--
ALTER TABLE `procedure_area_schedules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `providers`
--
ALTER TABLE `providers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `provider_requests`
--
ALTER TABLE `provider_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `provider_request_status_events`
--
ALTER TABLE `provider_request_status_events`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `services`
--
ALTER TABLE `services`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `source_payloads`
--
ALTER TABLE `source_payloads`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `treatments`
--
ALTER TABLE `treatments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `treatment_change_logs`
--
ALTER TABLE `treatment_change_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_medical_unit_id_foreign` FOREIGN KEY (`medical_unit_id`) REFERENCES `medical_units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_procedure_area_id_foreign` FOREIGN KEY (`procedure_area_id`) REFERENCES `procedure_areas` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `appointment_status_events`
--
ALTER TABLE `appointment_status_events`
  ADD CONSTRAINT `appointment_status_events_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_status_events_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `authorizations`
--
ALTER TABLE `authorizations`
  ADD CONSTRAINT `authorizations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `authorizations_hospitalization_id_foreign` FOREIGN KEY (`hospitalization_id`) REFERENCES `hospitalizations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `authorizations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `authorizations_treatment_id_foreign` FOREIGN KEY (`treatment_id`) REFERENCES `treatments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `authorizations_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `chronic_conditions`
--
ALTER TABLE `chronic_conditions`
  ADD CONSTRAINT `chronic_conditions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `chronic_conditions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `clinical_encounters`
--
ALTER TABLE `clinical_encounters`
  ADD CONSTRAINT `clinical_encounters_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `clinical_encounters_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clinical_encounters_medical_unit_id_foreign` FOREIGN KEY (`medical_unit_id`) REFERENCES `medical_units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `clinical_encounters_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clinical_encounters_procedure_area_id_foreign` FOREIGN KEY (`procedure_area_id`) REFERENCES `procedure_areas` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `clinical_records`
--
ALTER TABLE `clinical_records`
  ADD CONSTRAINT `clinical_records_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `clinical_records_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `contracted_services`
--
ALTER TABLE `contracted_services`
  ADD CONSTRAINT `contracted_services_institution_id_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contracted_services_medical_unit_id_foreign` FOREIGN KEY (`medical_unit_id`) REFERENCES `medical_units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contracted_services_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `delivery_reports`
--
ALTER TABLE `delivery_reports`
  ADD CONSTRAINT `delivery_reports_delivery_route_id_foreign` FOREIGN KEY (`delivery_route_id`) REFERENCES `delivery_routes` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `delivery_routes`
--
ALTER TABLE `delivery_routes`
  ADD CONSTRAINT `delivery_routes_messenger_profile_id_foreign` FOREIGN KEY (`messenger_profile_id`) REFERENCES `messenger_profiles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `delivery_routes_patient_order_id_foreign` FOREIGN KEY (`patient_order_id`) REFERENCES `patient_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `delivery_routes_provider_request_id_foreign` FOREIGN KEY (`provider_request_id`) REFERENCES `provider_requests` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_medical_unit_id_foreign` FOREIGN KEY (`medical_unit_id`) REFERENCES `medical_units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `doctors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `doctor_availability_exceptions`
--
ALTER TABLE `doctor_availability_exceptions`
  ADD CONSTRAINT `doctor_availability_exceptions_doctor_clinic_id_foreign` FOREIGN KEY (`doctor_clinic_id`) REFERENCES `doctor_clinics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_availability_exceptions_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `doctor_availability_rules`
--
ALTER TABLE `doctor_availability_rules`
  ADD CONSTRAINT `doctor_availability_rules_doctor_clinic_id_foreign` FOREIGN KEY (`doctor_clinic_id`) REFERENCES `doctor_clinics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_availability_rules_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `doctor_clinics`
--
ALTER TABLE `doctor_clinics`
  ADD CONSTRAINT `doctor_clinics_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_clinics_medical_unit_id_foreign` FOREIGN KEY (`medical_unit_id`) REFERENCES `medical_units` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_authorization_id_foreign` FOREIGN KEY (`authorization_id`) REFERENCES `authorizations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_hospitalization_id_foreign` FOREIGN KEY (`hospitalization_id`) REFERENCES `hospitalizations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_medication_delivery_id_foreign` FOREIGN KEY (`medication_delivery_id`) REFERENCES `medication_deliveries` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_treatment_id_foreign` FOREIGN KEY (`treatment_id`) REFERENCES `treatments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `hospitalizations`
--
ALTER TABLE `hospitalizations`
  ADD CONSTRAINT `hospitalizations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hospitalizations_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hospitalizations_hospital_id_foreign` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hospitalizations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hospitalizations_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `hospitalization_daily_notes`
--
ALTER TABLE `hospitalization_daily_notes`
  ADD CONSTRAINT `hospitalization_daily_notes_captured_by_foreign` FOREIGN KEY (`captured_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hospitalization_daily_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hospitalization_daily_notes_hospitalization_id_foreign` FOREIGN KEY (`hospitalization_id`) REFERENCES `hospitalizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hospitalization_daily_notes_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `hospitalization_procedures`
--
ALTER TABLE `hospitalization_procedures`
  ADD CONSTRAINT `hospitalization_procedures_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hospitalization_procedures_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hospitalization_procedures_hospitalization_id_foreign` FOREIGN KEY (`hospitalization_id`) REFERENCES `hospitalizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hospitalization_procedures_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `hospitals`
--
ALTER TABLE `hospitals`
  ADD CONSTRAINT `hospitals_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hospitals_medical_unit_id_foreign` FOREIGN KEY (`medical_unit_id`) REFERENCES `medical_units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hospitals_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hospitals_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `institutions`
--
ALTER TABLE `institutions`
  ADD CONSTRAINT `institutions_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `insurance_advisor_notifications`
--
ALTER TABLE `insurance_advisor_notifications`
  ADD CONSTRAINT `insurance_advisor_notifications_insurance_policy_id_foreign` FOREIGN KEY (`insurance_policy_id`) REFERENCES `insurance_policies` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `insurance_policies`
--
ALTER TABLE `insurance_policies`
  ADD CONSTRAINT `insurance_policies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `insurance_policies_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `insurance_policies_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_medical_unit_id_foreign` FOREIGN KEY (`medical_unit_id`) REFERENCES `medical_units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_items_pharmacy_product_id_foreign` FOREIGN KEY (`pharmacy_product_id`) REFERENCES `pharmacy_products` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `inventory_movements_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_movements_medical_unit_id_foreign` FOREIGN KEY (`medical_unit_id`) REFERENCES `medical_units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_hospital_id_foreign` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_hospitalization_id_foreign` FOREIGN KEY (`hospitalization_id`) REFERENCES `hospitalizations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `medical_units`
--
ALTER TABLE `medical_units`
  ADD CONSTRAINT `medical_units_institution_id_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `medications`
--
ALTER TABLE `medications`
  ADD CONSTRAINT `medications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `medications_medication_catalog_item_id_foreign` FOREIGN KEY (`medication_catalog_item_id`) REFERENCES `medication_catalog_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `medications_pharmacy_product_id_foreign` FOREIGN KEY (`pharmacy_product_id`) REFERENCES `pharmacy_products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `medications_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `medication_catalog_items`
--
ALTER TABLE `medication_catalog_items`
  ADD CONSTRAINT `medication_catalog_items_institution_id_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `medication_deliveries`
--
ALTER TABLE `medication_deliveries`
  ADD CONSTRAINT `medication_deliveries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `medication_deliveries_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `medications` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `medication_deliveries_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medication_deliveries_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `medication_deliveries_treatment_id_foreign` FOREIGN KEY (`treatment_id`) REFERENCES `treatments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `medication_deliveries_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `messenger_profiles`
--
ALTER TABLE `messenger_profiles`
  ADD CONSTRAINT `messenger_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `operational_profiles`
--
ALTER TABLE `operational_profiles`
  ADD CONSTRAINT `operational_profiles_medical_unit_id_foreign` FOREIGN KEY (`medical_unit_id`) REFERENCES `medical_units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `operational_profiles_operational_area_id_foreign` FOREIGN KEY (`operational_area_id`) REFERENCES `operational_areas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `operational_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patients_primary_doctor_id_foreign` FOREIGN KEY (`primary_doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patients_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patients_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `patient_diagnoses`
--
ALTER TABLE `patient_diagnoses`
  ADD CONSTRAINT `patient_diagnoses_chronic_condition_id_foreign` FOREIGN KEY (`chronic_condition_id`) REFERENCES `chronic_conditions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patient_diagnoses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patient_diagnoses_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patient_diagnoses_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_diagnoses_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `patient_orders`
--
ALTER TABLE `patient_orders`
  ADD CONSTRAINT `patient_orders_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `patient_order_items`
--
ALTER TABLE `patient_order_items`
  ADD CONSTRAINT `patient_order_items_patient_order_id_foreign` FOREIGN KEY (`patient_order_id`) REFERENCES `patient_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_order_items_pharmacy_product_id_foreign` FOREIGN KEY (`pharmacy_product_id`) REFERENCES `pharmacy_products` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `permissions`
--
ALTER TABLE `permissions`
  ADD CONSTRAINT `permissions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `permissions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pharmacy_warehouses`
--
ALTER TABLE `pharmacy_warehouses`
  ADD CONSTRAINT `pharmacy_warehouses_medical_unit_id_foreign` FOREIGN KEY (`medical_unit_id`) REFERENCES `medical_units` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `prescriptions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD CONSTRAINT `prescription_items_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `procedure_areas`
--
ALTER TABLE `procedure_areas`
  ADD CONSTRAINT `procedure_areas_medical_unit_id_foreign` FOREIGN KEY (`medical_unit_id`) REFERENCES `medical_units` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `procedure_area_schedules`
--
ALTER TABLE `procedure_area_schedules`
  ADD CONSTRAINT `procedure_area_schedules_procedure_area_id_foreign` FOREIGN KEY (`procedure_area_id`) REFERENCES `procedure_areas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `providers`
--
ALTER TABLE `providers`
  ADD CONSTRAINT `providers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `provider_requests`
--
ALTER TABLE `provider_requests`
  ADD CONSTRAINT `provider_requests_medical_unit_id_foreign` FOREIGN KEY (`medical_unit_id`) REFERENCES `medical_units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `provider_requests_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `provider_requests_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `provider_request_status_events`
--
ALTER TABLE `provider_request_status_events`
  ADD CONSTRAINT `provider_request_status_events_provider_request_id_foreign` FOREIGN KEY (`provider_request_id`) REFERENCES `provider_requests` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `roles_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `roles_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `treatments`
--
ALTER TABLE `treatments`
  ADD CONSTRAINT `treatments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `treatments_medication_id_foreign` FOREIGN KEY (`medication_id`) REFERENCES `medications` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `treatments_patient_diagnosis_id_foreign` FOREIGN KEY (`patient_diagnosis_id`) REFERENCES `patient_diagnoses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `treatments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatments_prescribing_doctor_id_foreign` FOREIGN KEY (`prescribing_doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `treatments_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `treatments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `treatment_change_logs`
--
ALTER TABLE `treatment_change_logs`
  ADD CONSTRAINT `treatment_change_logs_changed_by_user_id_foreign` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `treatment_change_logs_treatment_id_foreign` FOREIGN KEY (`treatment_id`) REFERENCES `treatments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
