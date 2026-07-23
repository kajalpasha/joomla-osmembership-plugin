# OS Membership Plugin - Complete Usage Guide

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Membership Types Overview](#membership-types-overview)
4. [Working with the API](#working-with-the-api)
5. [Database Schema](#database-schema)
6. [Code Examples](#code-examples)
7. [Troubleshooting](#troubleshooting)
8. [Advanced Configuration](#advanced-configuration)

---

## Installation

### Prerequisites

- Joomla 4.x
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- cURL enabled
- Database user with CREATE TABLE privileges

### Step 1: Upload Plugin Files

1. Download the plugin package
2. In Joomla Admin, go to **System → Install Extensions**
3. Choose **Upload Package File** or extract to `/plugins/system/osmembership/`
4. Click **Upload & Install**

### Step 2: Create Database Tables

The plugin automatically creates tables on first run. Alternatively, manually run the SQL:

```bash
mysql -u username -p database_name < sql/install.sql
```

### Step 3: Enable the Plugin

1. Go to **System → Manage → Plugins**
2. Search for "OS Membership"
3. Click the plugin name to open it
4. Set status to **Enabled**

### Step 4: Configure Plugin Settings

1. Still in plugin details, navigate to the **Configuration** tab
2. Fill in required settings:
   - **Enable Synchronization**: Yes
   - **Sync Interval**: 3600 (seconds, or 1 hour)
   - **API Key**: Your OS Membership API key
   - **API URL**: https://api.osmembership.com (or your custom URL)
3. Click **Save**

---

## Configuration

### Plugin Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `enable_sync` | Radio | 1 (Yes) | Enable/disable automatic sync |
| `sync_interval` | Integer | 3600 | Minimum seconds between syncs |
| `api_key` | Password | - | OS Membership API authentication key |
| `api_url` | Text | https://api.osmembership.com | Base URL for API calls |

### Example Configuration

```xml
<!-- In plugin parameters -->
<field name="enable_sync" type="radio" default="1" 
    label="PLG_SYSTEM_OSMEMBERSHIP_ENABLE_SYNC_LABEL">
    <option value="0">JNO</option>
    <option value="1">JYES</option>
</field>

<field name="sync_interval" type="integer" default="3600" 
    label="PLG_SYSTEM_OSMEMBERSHIP_SYNC_INTERVAL_LABEL" 
    min="300" />

<field name="api_key" type="password" 
    label="PLG_SYSTEM_OSMEMBERSHIP_API_KEY_LABEL" />

<field name="api_url" type="text" 
    default="https://api.osmembership.com" 
    label="PLG_SYSTEM_OSMEMBERSHIP_API_URL_LABEL" />
```

---

## Membership Types Overview

### Type Hierarchy

```
Free (Level 1)
  ↓
Basic (Level 2)
  ↓
Premium (Level 3)
  ↓
VIP (Level 4)
  ↓
Enterprise (Level 5)
```

### Quick Comparison Table

| Feature | Free | Basic | Premium | VIP | Enterprise |
|---------|------|-------|---------|-----|------------|
| **Group ID** | 6 | 7 | 8 | 9 | 10 |
| **Content Access** | Public | Member-only | All | All | All |
| **Downloads/Month** | 5 | Unlimited | Unlimited | Unlimited | Unlimited |
| **Forum Access** | Read-only | Full | Full | Full | Full |
| **API Access** | ✗ | ✗ | ✓ | ✓ | ✓ |
| **API Rate Limit** | - | - | 10K/day | 50K/day | Unlimited |
| **Priority Support** | ✗ | 24h | 4h | 1h | 30min |
| **Commercial Use** | ✗ | ✗ | ✗ | ✓ | ✓ |
| **White-label** | ✗ | ✗ | ✗ | ✓ | ✓ |
| **SSO/SAML** | ✗ | ✗ | ✗ | ✗ | ✓ |
| **Dedicated Manager** | ✗ | ✗ | ✗ | ✓ | ✓ |
| **Expiry (Days)** | None | 365 | 365 | 365 | 365 |

---

## Working with the API

### Getting API Credentials

1. Log in to OS Membership admin panel
2. Navigate to **Settings → API Keys**
3. Generate new API key
4. Copy key and enter in Joomla plugin settings

### API Endpoints Used

```
GET  /members                    - Fetch all members
GET  /members/{id}              - Get specific member
POST /members                    - Create new member
PUT  /members/{id}              - Update member
GET  /plans                      - Get membership plans
GET  /plans/{id}                - Get specific plan
```

### Example API Response

```json
{
  "members": [
    {
      "id": 123,
      "email": "user@example.com",
      "name": "John Doe",
      "membership_type": "premium",
      "status": "active",
      "joined_date": "2024-01-15",
      "username": "johndoe"
    }
  ]
}
```

---

## Database Schema

### Core Tables

#### `#__osmembership_plans`
Stores membership plan definitions loaded from API

```sql
SELECT * FROM `#__osmembership_plans` 
WHERE published = 1 
ORDER BY level ASC;
```

#### `#__osmembership_members`
Stores synced member data

```sql
SELECT * FROM `#__osmembership_members` 
WHERE status = 'active' 
AND expiry_date > NOW();
```

#### `#__osmembership_member_audit`
Audit trail of membership changes

```sql
SELECT * FROM `#__osmembership_member_audit` 
WHERE user_id = 123 
ORDER BY created_date DESC;
```

#### `#__osmembership_api_keys`
API keys for members with API access

```sql
SELECT * FROM `#__osmembership_api_keys` 
WHERE user_id = 456 
AND active = 1;
```

#### `#__osmembership_renewal_reminders`
Scheduled renewal notification reminders

```sql
SELECT * FROM `#__osmembership_renewal_reminders` 
WHERE sent = 0 
AND reminder_date <= NOW();
```

---

## Code Examples

### Example 1: Get Member Membership Type

```php
<?php
use Kajalpasha\Plugin\System\Osmembership\Service\MembershipTypeHandler;

// Get database
$db = JFactory::getDbo();

// Initialize handler
$handler = new MembershipTypeHandler($db);

// Get all membership types
$types = $handler->getMembershipTypes();

// Get specific type by slug
$premiumType = $handler->getMembershipTypeBySlug('premium');
echo "Premium Group ID: " . $premiumType['id'];
```

### Example 2: Process Member Membership

```php
<?php
// Get the plugin
$plugin = JPluginHelper::getPlugin('system', 'osmembership');
$dispatcher = JFactory::getApplication()->getDispatcher();
$db = JFactory::getDbo();

// Create plugin instance
$osmembership = new Osmembership($dispatcher, (array)$plugin->params, $db);

// Member data from API
$member = [
    'id' => 789,
    'email' => 'newuser@example.com',
    'name' => 'Jane Smith',
    'membership_type' => 'vip',
    'status' => 'active'
];

// Process membership
$config = $osmembership->processMembershipByType($member);

// Access results
echo "Assigned to group: " . $config['group_id'];
echo "Features: " . implode(', ', $config['features']);
echo "Expiry in days: " . $config['expiry_days'];
```

### Example 3: Check Member Access

```php
<?php
// Get member record
$db = JFactory::getDbo();
$query = $db->getQuery(true)
    ->select('*')
    ->from($db->quoteName('#__osmembership_members'))
    ->where($db->quoteName('user_id') . ' = ' . (int)$userId);

$member = $db->setQuery($query)->loadObject();

if ($member && $member->status === 'active') {
    if ($member->expiry_date && $member->expiry_date > date('Y-m-d H:i:s')) {
        echo "Member has active access";
    } else {
        echo "Membership expired";
    }
}
```

### Example 4: Get Member by Email

```php
<?php
use Kajalpasha\Plugin\System\Osmembership\Service\OsmembershipService;

$db = JFactory::getDbo();
$apiKey = 'your_api_key';
$apiUrl = 'https://api.osmembership.com';

// Create service
$service = new OsmembershipService($apiKey, $apiUrl, $db);

// Query member from Joomla users
$query = $db->getQuery(true)
    ->select('u.id, m.membership_type, m.expiry_date')
    ->from($db->quoteName('#__users', 'u'))
    ->leftJoin($db->quoteName('#__osmembership_members', 'm') 
        . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('m.user_id'))
    ->where($db->quoteName('u.email') . ' = ' . $db->quote('user@example.com'));

$result = $db->setQuery($query)->loadObject();
```

### Example 5: Get Expiring Memberships

```php
<?php
// Find members expiring within 30 days
$db = JFactory::getDbo();
$thirtyDaysLater = date('Y-m-d', strtotime('+30 days'));

$query = $db->getQuery(true)
    ->select($db->quoteName(['id', 'user_id', 'membership_type', 'expiry_date']))
    ->from($db->quoteName('#__osmembership_members'))
    ->where($db->quoteName('expiry_date') . ' BETWEEN NOW() AND ' . $db->quote($thirtyDaysLater))
    ->where($db->quoteName('status') . ' = ' . $db->quote('active'))
    ->order($db->quoteName('expiry_date') . ' ASC');

$expiringMembers = $db->setQuery($query)->loadAssocList();

foreach ($expiringMembers as $member) {
    echo "User " . $member['user_id'] . " (" . $member['membership_type'] . ") expires: " . $member['expiry_date'];
}
```

### Example 6: Custom Group Assignment

```php
<?php
// Assign user to membership group
$db = JFactory::getDbo();
$userId = 123;
$groupId = 8; // Premium group

// Remove from all other membership groups
$query = $db->getQuery(true)
    ->delete($db->quoteName('#__user_usergroup_map'))
    ->where($db->quoteName('user_id') . ' = ' . (int)$userId)
    ->where($db->quoteName('group_id') . ' IN (6, 7, 8, 9, 10)');

$db->setQuery($query)->execute();

// Assign to new group
$query = $db->getQuery(true)
    ->insert($db->quoteName('#__user_usergroup_map'))
    ->columns([$db->quoteName('user_id'), $db->quoteName('group_id')])
    ->values((int)$userId . ', ' . (int)$groupId);

$db->setQuery($query)->execute();
```

---

## Troubleshooting

### Sync Not Running

**Symptoms:** Members not syncing from OS Membership

**Solutions:**
1. Check plugin is enabled: **System → Manage → Plugins → OS Membership**
2. Verify "Enable Synchronization" is set to Yes
3. Check sync interval hasn't just passed (minimum time between syncs)
4. Review logs: **System → System Information → Log Files**

### API Connection Error

**Symptoms:** "API connection failed" in logs

**Solutions:**
1. Verify API Key is correct
2. Check API URL is accessible
3. Verify firewall allows outbound HTTPS connections
4. Test API with curl: `curl -H "Authorization: Bearer YOUR_KEY" https://api.osmembership.com/members`

### Users Not Being Created

**Symptoms:** Members synced but no Joomla users created

**Solutions:**
1. Check database user has INSERT permission on `#__users`
2. Verify email addresses are unique
3. Check username generation (spaces replaced with underscores)
4. Review error logs for specific database errors

### Groups Not Assigned

**Symptoms:** Users created but not assigned to membership groups

**Solutions:**
1. Verify group IDs exist: **System → User Groups**
2. Check group IDs match in plugin configuration
3. Ensure `#__user_usergroup_map` table has proper permissions
4. Verify membership type is correctly identified

### High Database CPU Usage

**Symptoms:** Plugin sync causes high CPU/database load

**Solutions:**
1. Increase sync interval (set to 7200 or higher)
2. Disable sync during peak hours (use cron task instead)
3. Add indexes to `membership_type` and `user_id` columns
4. Batch sync members instead of individual processing

---

## Advanced Configuration

### Custom Membership Type

To add a custom membership type beyond the built-in five:

#### 1. Insert into `#__osmembership_plans`

```sql
INSERT INTO `#__osmembership_plans` 
(`name`, `slug`, `description`, `level`, `permissions`, `published`, `ordering`) 
VALUES (
    'Professional',
    'professional',
    'Professional tier with custom features',
    3.5,
    JSON_OBJECT(
        'api_access', true,
        'api_rate_limit', 15000,
        'priority_support', true,
        'support_response_time', 6
    ),
    1,
    3
);
```

#### 2. Add Handler in MembershipTypeHandler.php

```php
// In processMembershipByType() match statement
'professional' => $this->processProfessionalMembers($member, $typeData),

// Add private method
private function processProfessionalMembers($member, $typeData)
{
    $config = [
        'group_id' => 11,
        'permissions' => [
            'api_access' => true,
            'api_rate_limit' => 15000,
            'priority_support' => true,
        ],
        'expiry_days' => 365,
    ];
    
    $this->assignUserGroup($member, $config['group_id']);
    $this->recordMembershipTier($member, 'professional', $config);
    
    return $config;
}
```

#### 3. Create Joomla User Group

```sql
INSERT INTO `#__usergroups` 
(`parent_id`, `title`, `description`) 
VALUES (1, 'Professional Members', 'Custom professional tier');
```

### Scheduled Sync with Cron

Instead of relying on page loads for sync, use server cron:

```bash
# Run sync every hour
0 * * * * curl -s "https://yoursite.com/index.php?option=com_api&task=sync&key=YOUR_API_KEY" > /dev/null 2>&1
```

### API Rate Limiting Per User

```php
// Check API usage before allowing request
$db = JFactory::getDbo();
$query = $db->getQuery(true)
    ->select($db->quoteName(['calls_today', 'rate_limit']))
    ->from($db->quoteName('#__osmembership_api_keys'))
    ->where($db->quoteName('api_key') . ' = ' . $db->quote($apiKey));

$key = $db->setQuery($query)->loadObject();

if ($key->calls_today >= $key->rate_limit) {
    throw new \Exception('API rate limit exceeded');
}
```

### Webhook Integration

Receive real-time membership updates from OS Membership:

```php
// In your webhook handler
$data = json_decode(file_get_contents('php://input'), true);

$handler = new MembershipTypeHandler($db);
$config = $handler->processMembershipByType($data['member']);

// Log the update
Log::add('Webhook membership update for: ' . $data['member']['email'], Log::INFO, 'plg_system_osmembership');
```

---

## Support & Documentation

For more information:
- [Joomla Plugin Development](https://docs.joomla.org/Developing_a_System_Plugin)
- [OS Membership API Documentation](https://docs.osmembership.com/api)
- [Plugin Repository](https://github.com/kajalpasha/joomla-osmembership-plugin)

---

## License

GNU General Public License version 2 or later

**Copyright © 2024 Your Company. All rights reserved.**
