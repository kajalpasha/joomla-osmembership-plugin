-- Joomla 4 OS Membership Plugin - Database Schema
-- This script creates the necessary tables for the OS Membership plugin

-- Table: OS Membership Plans
-- Stores membership plan definitions
CREATE TABLE IF NOT EXISTS `#__osmembership_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL UNIQUE,
  `description` text,
  `level` int(11) DEFAULT 0,
  `permissions` longtext COMMENT 'JSON encoded permissions',
  `features` longtext COMMENT 'JSON encoded features list',
  `published` tinyint(1) DEFAULT 1,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ordering` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_published` (`published`),
  KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: OS Membership Members
-- Stores member data synchronized from OS Membership
CREATE TABLE IF NOT EXISTS `#__osmembership_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `membership_type` varchar(100),
  `external_id` varchar(255) COMMENT 'ID from external OS Membership system',
  `status` varchar(50) DEFAULT 'active' COMMENT 'active, inactive, expired, suspended',
  `joined_date` datetime,
  `expiry_date` datetime COMMENT 'Membership expiry date',
  `renewal_date` datetime COMMENT 'Last renewal date',
  `config_data` longtext COMMENT 'JSON encoded membership configuration',
  `last_synced` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_id` (`user_id`),
  KEY `idx_external_id` (`external_id`),
  KEY `idx_status` (`status`),
  KEY `idx_membership_type` (`membership_type`),
  KEY `idx_expiry_date` (`expiry_date`),
  FOREIGN KEY (`user_id`) REFERENCES `#__users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: OS Membership Member Audit
-- Tracks all membership changes for audit trail
CREATE TABLE IF NOT EXISTS `#__osmembership_member_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `membership_tier` varchar(100),
  `old_tier` varchar(100) COMMENT 'Previous membership tier',
  `action` varchar(50) DEFAULT 'update' COMMENT 'create, update, upgrade, downgrade, expire, renew',
  `config_data` longtext COMMENT 'JSON encoded configuration',
  `notes` text,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_membership_tier` (`membership_tier`),
  KEY `idx_action` (`action`),
  KEY `idx_created_date` (`created_date`),
  FOREIGN KEY (`user_id`) REFERENCES `#__users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `#__users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: OS Membership API Keys
-- Stores API keys for members with API access
CREATE TABLE IF NOT EXISTS `#__osmembership_api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `key_name` varchar(255) NOT NULL,
  `api_key` varchar(255) NOT NULL UNIQUE,
  `api_secret` varchar(255) NOT NULL,
  `rate_limit` int(11) DEFAULT 1000 COMMENT 'Requests per day',
  `calls_today` int(11) DEFAULT 0,
  `last_reset` datetime DEFAULT CURRENT_TIMESTAMP,
  `active` tinyint(1) DEFAULT 1,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_used` datetime,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_api_key` (`api_key`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_active` (`active`),
  FOREIGN KEY (`user_id`) REFERENCES `#__users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: OS Membership Renewal Reminders
-- Tracks renewal reminder notifications
CREATE TABLE IF NOT EXISTS `#__osmembership_renewal_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reminder_date` datetime NOT NULL COMMENT 'When reminder should be sent',
  `days_before_expiry` int(11) DEFAULT 30,
  `sent` tinyint(1) DEFAULT 0,
  `sent_date` datetime,
  `email_sent_to` varchar(255),
  `status` varchar(50) DEFAULT 'pending' COMMENT 'pending, sent, failed',
  `error_message` text,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_reminder_date` (`reminder_date`),
  KEY `idx_status` (`status`),
  KEY `idx_sent` (`sent`),
  FOREIGN KEY (`user_id`) REFERENCES `#__users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: OS Membership Account Managers
-- Assigns dedicated account managers to VIP and Enterprise members
CREATE TABLE IF NOT EXISTS `#__osmembership_account_managers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_user_id` int(11) NOT NULL,
  `manager_user_id` int(11) NOT NULL,
  `assigned_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(50) DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_member_user_id` (`member_user_id`),
  KEY `idx_manager_user_id` (`manager_user_id`),
  FOREIGN KEY (`member_user_id`) REFERENCES `#__users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`manager_user_id`) REFERENCES `#__users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: OS Membership SSO Integration
-- Stores SSO configuration for Enterprise members
CREATE TABLE IF NOT EXISTS `#__osmembership_sso_integration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL UNIQUE,
  `sso_type` varchar(50) DEFAULT 'saml' COMMENT 'saml, oauth2, ldap',
  `sso_identifier` varchar(255) NOT NULL,
  `metadata` longtext COMMENT 'JSON encoded SSO metadata',
  `active` tinyint(1) DEFAULT 1,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_sso_type` (`sso_type`),
  FOREIGN KEY (`user_id`) REFERENCES `#__users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: OS Membership Sync Log
-- Tracks synchronization operations
CREATE TABLE IF NOT EXISTS `#__osmembership_sync_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_type` varchar(50) DEFAULT 'members' COMMENT 'members, plans, sync_all',
  `status` varchar(50) DEFAULT 'completed' COMMENT 'running, completed, failed',
  `members_synced` int(11) DEFAULT 0,
  `members_created` int(11) DEFAULT 0,
  `members_updated` int(11) DEFAULT 0,
  `members_failed` int(11) DEFAULT 0,
  `error_message` longtext,
  `start_time` datetime,
  `end_time` datetime,
  `duration_seconds` int(11),
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sync_type` (`sync_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_date` (`created_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default membership plans
INSERT INTO `#__osmembership_plans` (`name`, `slug`, `description`, `level`, `permissions`, `features`, `published`, `ordering`) VALUES
(
  'Free',
  'free',
  'Free membership tier with basic access',
  1,
  '{
    "access_forum": true,
    "access_library": true,
    "download_limit": 5,
    "priority_support": false,
    "api_access": false,
    "commercial_use": false
  }',
  '["basic_content", "community_forum", "knowledge_base_read_only"]',
  1,
  1
),
(
  'Basic',
  'basic',
  'Basic membership with core features',
  2,
  '{
    "access_forum": true,
    "access_library": true,
    "download_limit": null,
    "priority_support": true,
    "api_access": false,
    "commercial_use": false,
    "support_response_time": 24
  }',
  '["all_public_content", "member_content", "forum_posting", "priority_support_24h", "newsletter_access"]',
  1,
  2
),
(
  'Premium',
  'premium',
  'Premium membership with API and analytics',
  3,
  '{
    "access_forum": true,
    "access_library": true,
    "download_limit": null,
    "priority_support": true,
    "api_access": true,
    "api_rate_limit": 10000,
    "commercial_use": false,
    "support_response_time": 4,
    "advanced_analytics": true
  }',
  '["all_public_content", "all_member_content", "advanced_api", "priority_support_4h", "analytics_dashboard", "webinar_access"]',
  1,
  3
),
(
  'VIP',
  'vip',
  'VIP membership with white-label and enterprise features',
  4,
  '{
    "access_forum": true,
    "access_library": true,
    "download_limit": null,
    "priority_support": true,
    "api_access": true,
    "api_rate_limit": 50000,
    "commercial_use": true,
    "support_response_time": 1,
    "advanced_analytics": true,
    "white_label": true,
    "custom_integrations": true
  }',
  '["all_premium_features", "unlimited_api", "priority_support_1h", "dedicated_account_manager", "vip_events_access", "commercial_license", "white_label_options"]',
  1,
  4
),
(
  'Enterprise',
  'enterprise',
  'Enterprise membership with unlimited features and support',
  5,
  '{
    "access_forum": true,
    "access_library": true,
    "download_limit": null,
    "priority_support": true,
    "api_access": true,
    "api_rate_limit": null,
    "commercial_use": true,
    "support_response_time": 0.5,
    "advanced_analytics": true,
    "white_label": true,
    "custom_integrations": true,
    "sso_integration": true,
    "dedicated_infrastructure": true,
    "custom_development": true
  }',
  '["unlimited_everything", "dedicated_infrastructure", "sla_guarantee_99_9", "support_24_7_365", "custom_development", "team_collaboration", "sso_saml", "advanced_security"]',
  1,
  5
);

-- Create indexes for performance
CREATE INDEX idx_members_user_membership ON `#__osmembership_members` (`user_id`, `membership_type`);
CREATE INDEX idx_members_expiry_status ON `#__osmembership_members` (`expiry_date`, `status`);
CREATE INDEX idx_audit_user_tier ON `#__osmembership_member_audit` (`user_id`, `membership_tier`);
CREATE INDEX idx_sync_log_dates ON `#__osmembership_sync_log` (`start_time`, `end_time`);
