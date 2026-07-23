# Membership Type Processing Guide

## Overview

The Joomla 4 OS Membership plugin includes a sophisticated membership type handling system that automatically processes different membership tiers with specific code blocks for each type. This document explains how to use and customize the membership type functionality.

## Membership Types

The plugin supports five built-in membership tiers, each with specific features and permissions:

### 1. FREE Membership

**Group ID:** 6

**Features:**
- Basic access to public content
- Limited downloads (5 per month)
- Community forum access
- No priority support
- No API access
- No commercial use allowed
- No expiry date

**Permissions:**
```php
'permissions' => [
    'access_forum' => true,
    'access_library' => true,
    'download_limit' => 5,
    'priority_support' => false,
    'api_access' => false,
    'commercial_use' => false,
]
```

**Access Methods:**
```php
// From plugin code
$plugin = JPluginHelper::getPlugin('system', 'osmembership');
$osmembership = new Osmembership(null, (array)$plugin->params);
$config = $osmembership->processMembershipByType($member);
```

---

### 2. BASIC Membership

**Group ID:** 7

**Features:**
- Full access to public and member-only content
- Unlimited downloads
- Priority support (24-hour response)
- Community forum with posting rights
- Email newsletters
- Annual renewal (365 days expiry)
- Renewal reminder 30 days before expiry

**Permissions:**
```php
'permissions' => [
    'access_forum' => true,
    'access_library' => true,
    'download_limit' => null, // Unlimited
    'priority_support' => true,
    'api_access' => false,
    'commercial_use' => false,
    'support_response_time' => 24, // hours
]
```

**Code Block Example:**
```php
private function processBasicMembers($member, $typeData)
{
    // Assign to basic group
    $this->assignUserGroup($member, 7);
    
    // Enable notifications
    $this->enableEmailNotifications($member);
    
    // Schedule renewal reminder
    $this->scheduleRenewalReminder($member, 30);
    
    // Record membership for audit
    $this->recordMembershipTier($member, 'basic', $config);
}
```

---

### 3. PREMIUM Membership

**Group ID:** 8

**Features:**
- All basic features
- Advanced API access (10,000 requests/day)
- Priority support (4-hour response)
- Custom content recommendations
- Advanced analytics dashboard
- Webinar access
- Early access to new features
- Annual renewal (365 days expiry)
- Renewal reminder 30 days before expiry

**Permissions:**
```php
'permissions' => [
    'access_forum' => true,
    'access_library' => true,
    'download_limit' => null, // Unlimited
    'priority_support' => true,
    'api_access' => true,
    'api_rate_limit' => 10000,
    'commercial_use' => false,
    'support_response_time' => 4, // hours
    'advanced_analytics' => true,
]
```

**Code Block Example:**
```php
private function processPremiumMembers($member, $typeData)
{
    // Assign to premium group
    $this->assignUserGroup($member, 8);
    
    // Enable API access with rate limiting
    $this->enableAPIAccess($member, 10000);
    
    // Enable analytics dashboard
    $this->enableAnalyticsDashboard($member);
    
    // Enable email notifications
    $this->enableEmailNotifications($member);
    
    // Record and schedule renewal
    $this->recordMembershipTier($member, 'premium', $config);
    $this->scheduleRenewalReminder($member, 30);
}
```

---

### 4. VIP Membership

**Group ID:** 9

**Features:**
- All premium features
- Dedicated account manager
- Unlimited priority support (1-hour response)
- Custom integrations
- Exclusive VIP events
- Branded resources
- Commercial use license
- White-label options
- Annual renewal (365 days expiry)
- Renewal reminder 60 days before expiry
- 10% discount on renewals

**Permissions:**
```php
'permissions' => [
    'access_forum' => true,
    'access_library' => true,
    'download_limit' => null, // Unlimited
    'priority_support' => true,
    'api_access' => true,
    'api_rate_limit' => 50000,
    'commercial_use' => true,
    'support_response_time' => 1, // hour
    'advanced_analytics' => true,
    'white_label' => true,
    'custom_integrations' => true,
]
```

**Code Block Example:**
```php
private function processVIPMembers($member, $typeData)
{
    // Assign to VIP group
    $this->assignUserGroup($member, 9);
    
    // Enable API access with higher limits
    $this->enableAPIAccess($member, 50000);
    
    // Enable all analytics and features
    $this->enableAnalyticsDashboard($member);
    
    // Assign dedicated account manager
    $this->assignDedicatedAccountManager($member);
    
    // Enable white-label features
    $this->enableWhiteLabelFeatures($member);
    
    // Record and schedule renewal
    $this->recordMembershipTier($member, 'vip', $config);
    $this->scheduleRenewalReminder($member, 60);
}
```

---

### 5. ENTERPRISE Membership

**Group ID:** 10

**Features:**
- Full custom solutions
- Dedicated infrastructure
- SLA guarantee (99.9% uptime)
- Unlimited everything
- 24/7/365 support
- Custom feature development
- Team collaboration tools
- SSO/SAML integration
- Annual renewal (365 days expiry)
- Renewal reminder 90 days before expiry
- 15% discount on renewals
- Requires approval for signup

**Permissions:**
```php
'permissions' => [
    'access_forum' => true,
    'access_library' => true,
    'download_limit' => null, // Unlimited
    'priority_support' => true,
    'api_access' => true,
    'api_rate_limit' => null, // Unlimited
    'commercial_use' => true,
    'support_response_time' => 0.5, // 30 minutes
    'advanced_analytics' => true,
    'white_label' => true,
    'custom_integrations' => true,
    'sso_integration' => true,
    'dedicated_infrastructure' => true,
    'custom_development' => true,
]
```

**Code Block Example:**
```php
private function processEnterpriseMembers($member, $typeData)
{
    // Assign to enterprise group
    $this->assignUserGroup($member, 10);
    
    // Enable unlimited API access
    $this->enableAPIAccess($member, null); // null = unlimited
    
    // Enable all premium features
    $this->enableAnalyticsDashboard($member);
    $this->enableEmailNotifications($member);
    
    // Assign dedicated account manager
    $this->assignDedicatedAccountManager($member);
    
    // Enable white-label and SSO
    $this->enableWhiteLabelFeatures($member);
    $this->enableSSO($member);
    
    // Setup dedicated infrastructure
    $this->setupDedicatedInfrastructure($member);
    
    // Record and schedule renewal
    $this->recordMembershipTier($member, 'enterprise', $config);
    $this->scheduleRenewalReminder($member, 90);
}
```

---

## How to Use the Membership Type Handler

### In Plugin Code

```php
use Kajalpasha\Plugin\System\Osmembership\Service\MembershipTypeHandler;

// Initialize the handler
$handler = new MembershipTypeHandler($db);

// Get all membership types
$types = $handler->getMembershipTypes();

// Process a member with their membership type
$membershipConfig = $handler->processMembershipByType([
    'id' => 123,
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'membership_type' => 'premium',
    'status' => 'active',
]);

// Access configuration results
$groupId = $membershipConfig['group_id'];
$permissions = $membershipConfig['permissions'];
$features = $membershipConfig['features'];
```

### From External Components

```php
// Get the plugin
$plugin = JPluginHelper::getPlugin('system', 'osmembership');

// Create instance
$osmembership = new Osmembership(
    JFactory::getApplication()->getDispatcher(),
    (array)$plugin->params
);

// Process membership
$config = $osmembership->processMembershipByType($member);

// Get membership types
$types = $osmembership->getMembershipTypes();

// Get specific type
$premiumType = $osmembership->getMembershipTypeBySlug('premium');
```

---

## Adding Custom Membership Types

To add a custom membership type, modify the `processMembershipByType()` method in `MembershipTypeHandler.php`:

### Step 1: Add to Match Statement

```php
$result = match ($typeData['slug']) {
    'free' => $this->processFreeMembers($member, $typeData),
    'basic' => $this->processBasicMembers($member, $typeData),
    'premium' => $this->processPremiumMembers($member, $typeData),
    'vip' => $this->processVIPMembers($member, $typeData),
    'enterprise' => $this->processEnterpriseMembers($member, $typeData),
    'custom_tier' => $this->processCustomTier($member, $typeData), // Add this
    default => $this->processCustomMembership($member, $typeData),
};
```

### Step 2: Create Handler Method

```php
/**
 * Process CUSTOM_TIER membership tier
 *
 * @param   array  $member    The member data
 * @param   array  $typeData  The membership type data
 *
 * @return  array
 * @since   1.0.0
 */
private function processCustomTier($member, $typeData)
{
    $config = [
        'group_id' => 11, // Your custom group ID
        'permissions' => [
            'access_forum' => true,
            'api_access' => true,
            'api_rate_limit' => 5000,
            // Add your custom permissions
        ],
        'features' => [
            'custom_feature_1',
            'custom_feature_2',
        ],
        'expiry_days' => 365,
        'renewal_required' => true,
    ];

    $this->assignUserGroup($member, $config['group_id']);
    $this->setMemberPermissions($member, $config['permissions']);
    $this->recordMembershipTier($member, 'custom_tier', $config);

    return $config;
}
```

---

## Database Requirements

The plugin uses the following tables:

### `#__osmembership_plans`
Stores membership plan definitions:
- `id` - Plan ID
- `name` - Plan name
- `slug` - URL-friendly identifier
- `description` - Plan description
- `level` - Plan level/order
- `permissions` - JSON encoded permissions
- `published` - Is plan active

### `#__osmembership_members`
Stores member membership data:
- `id` - Record ID
- `user_id` - Joomla user ID
- `membership_type` - Current membership type
- `external_id` - External system ID
- `status` - Member status (active, inactive, expired)
- `joined_date` - When member joined
- `expiry_date` - When membership expires
- `config_data` - JSON encoded configuration
- `last_synced` - Last sync timestamp

### `#__osmembership_member_audit`
Audit trail for membership changes:
- `id` - Record ID
- `user_id` - Joomla user ID
- `membership_tier` - Tier name
- `config_data` - Configuration at time of change
- `created_date` - When change occurred

---

## Event Hooks

The plugin triggers on these Joomla events:

- `onAfterInitialise` - Plugin initialization
- Sync process checks membership types and processes accordingly

---

## Troubleshooting

### Members not being assigned correct groups
1. Check `#__osmembership_plans` table has data
2. Verify membership_type in member data matches plan slug
3. Check Joomla user groups exist with correct IDs

### API access not working
1. Verify API rate limit is set correctly
2. Check API keys are generated and assigned
3. Review API documentation for rate limit formats

### Renewal reminders not sending
1. Verify scheduled task is configured
2. Check email notification settings
3. Review logs for task execution errors

---

## Extending Hook Points

To add custom processing at different stages:

1. **After user creation** - Modify `createUser()` method
2. **After group assignment** - Modify `updateUserGroups()` method
3. **Custom permissions** - Modify `setMemberPermissions()` method
4. **Before sync** - Add logic in `syncMembers()` method

---

## Performance Optimization

For large member databases:

1. Implement caching in `getMembershipTypes()`
2. Use batch processing in `syncMembers()`
3. Add database indexes on `user_id` and `membership_type`
4. Consider async processing for membership changes

---

## Security Considerations

- API keys are stored encrypted in plugin parameters
- All database queries use parameterized statements
- Membership data is tied to authenticated Joomla users
- Audit trail tracks all membership changes
- Permissions are validated at multiple levels

