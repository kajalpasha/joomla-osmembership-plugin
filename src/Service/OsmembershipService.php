<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Osmembership
 *
 * @copyright   (C) 2024 Your Company
 * @license     GNU General Public License version 2 or later
 */

namespace Kajalpasha\Plugin\System\Osmembership\Service;

defined('_JEXEC') or die;

use Joomla\Http\HttpFactory;
use Joomla\Http\Response;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Log\Log;

/**
 * OS Membership Service
 *
 * Handles all API communication with OS Membership
 *
 * @since  1.0.0
 */
class OsmembershipService
{
    /**
     * API Key
     *
     * @var    string
     * @since  1.0.0
     */
    private $apiKey;

    /**
     * API URL
     *
     * @var    string
     * @since  1.0.0
     */
    private $apiUrl;

    /**
     * Database driver
     *
     * @var    DatabaseInterface
     * @since  1.0.0
     */
    private $db;

    /**
     * HTTP client
     *
     * @var    HttpFactory
     * @since  1.0.0
     */
    private $httpClient;

    /**
     * Membership Type Handler
     *
     * @var    MembershipTypeHandler
     * @since  1.0.0
     */
    private $membershipTypeHandler;

    /**
     * Constructor
     *
     * @param   string              $apiKey  The API key
     * @param   string              $apiUrl  The API URL
     * @param   DatabaseInterface   $db      The database driver
     *
     * @since   1.0.0
     */
    public function __construct($apiKey, $apiUrl, DatabaseInterface $db)
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->db = $db;
        $this->httpClient = HttpFactory::getHttp();
        $this->membershipTypeHandler = new MembershipTypeHandler($db);
    }

    /**
     * Sync members from OS Membership to Joomla
     *
     * @return  void
     * @throws  \Exception
     * @since   1.0.0
     */
    public function syncMembers()
    {
        try {
            $members = $this->fetchMembers();
            
            if (empty($members)) {
                return;
            }

            foreach ($members as $member) {
                $this->syncMember($member);
            }
        } catch (\Exception $e) {
            throw new \Exception('Failed to sync members: ' . $e->getMessage());
        }
    }

    /**
     * Fetch members from API
     *
     * @return  array
     * @throws  \Exception
     * @since   1.0.0
     */
    private function fetchMembers()
    {
        $url = $this->apiUrl . '/members';
        
        try {
            $response = $this->httpClient->get($url, $this->getHeaders());
            
            if ($response->code !== 200) {
                throw new \Exception('API returned status code: ' . $response->code);
            }

            $data = json_decode($response->body, true);
            
            if (!isset($data['members'])) {
                throw new \Exception('Invalid API response format');
            }

            return $data['members'];
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch members: ' . $e->getMessage());
        }
    }

    /**
     * Sync individual member
     *
     * @param   array  $member  The member data
     *
     * @return  void
     * @since   1.0.0
     */
    private function syncMember($member)
    {
        try {
            $userId = $this->findOrCreateUser($member);
            $member['user_id'] = $userId;
            
            // Process membership based on type
            $membershipConfig = $this->membershipTypeHandler->processMembershipByType($member);
            
            // Update user groups based on membership type
            $this->updateUserGroups($userId, $membershipConfig);
            $this->recordMembershipData($userId, $member, $membershipConfig);
        } catch (\Exception $e) {
            Log::add(
                'Failed to sync member ' . ($member['email'] ?? 'unknown') . ': ' . $e->getMessage(),
                Log::WARNING,
                'plg_system_osmembership'
            );
        }
    }

    /**
     * Find or create a Joomla user
     *
     * @param   array  $member  The member data
     *
     * @return  integer
     * @throws  \Exception
     * @since   1.0.0
     */
    private function findOrCreateUser($member)
    {
        $email = $member['email'] ?? null;
        
        if (empty($email)) {
            throw new \Exception('Member email is required');
        }

        // Check if user exists
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__users'))
            ->where($this->db->quoteName('email') . ' = ' . $this->db->quote($email))
            ->setLimit(1);

        $userId = $this->db->setQuery($query)->loadResult();

        if ($userId) {
            // Update existing user
            $this->updateUser($userId, $member);
            return $userId;
        }

        // Create new user
        return $this->createUser($member);
    }

    /**
     * Create a new Joomla user
     *
     * @param   array  $member  The member data
     *
     * @return  integer
     * @throws  \Exception
     * @since   1.0.0
     */
    private function createUser($member)
    {
        $username = $member['username'] ?? str_replace('@', '_', $member['email']);
        
        // Ensure username is unique
        $username = $this->getUniqueUsername($username);

        $userData = [
            'name'     => $member['name'] ?? $member['email'],
            'username' => $username,
            'email'    => $member['email'],
            'password' => bin2hex(random_bytes(16)),
            'block'    => 0,
            'sendEmail' => 0
        ];

        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__users'))
            ->columns($this->db->quoteName(array_keys($userData)))
            ->values(implode(',', array_map([$this->db, 'quote'], array_values($userData))));

        $this->db->setQuery($query)->execute();
        
        return $this->db->insertid();
    }

    /**
     * Update existing user
     *
     * @param   integer  $userId  The user ID
     * @param   array    $member  The member data
     *
     * @return  void
     * @since   1.0.0
     */
    private function updateUser($userId, $member)
    {
        $updateData = [];
        
        if (isset($member['name'])) {
            $updateData['name'] = $member['name'];
        }
        
        if (isset($member['email'])) {
            $updateData['email'] = $member['email'];
        }

        if (empty($updateData)) {
            return;
        }

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__users'))
            ->where($this->db->quoteName('id') . ' = ' . (int) $userId);

        foreach ($updateData as $column => $value) {
            $query->set($this->db->quoteName($column) . ' = ' . $this->db->quote($value));
        }

        $this->db->setQuery($query)->execute();
    }

    /**
     * Get unique username
     *
     * @param   string  $username  The base username
     *
     * @return  string
     * @since   1.0.0
     */
    private function getUniqueUsername($username)
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__users'))
            ->where($this->db->quoteName('username') . ' = ' . $this->db->quote($username));

        $count = $this->db->setQuery($query)->loadResult();

        if ($count > 0) {
            return $username . '_' . time();
        }

        return $username;
    }

    /**
     * Update user groups based on membership configuration
     *
     * @param   integer  $userId      The user ID
     * @param   array    $membershipConfig  The membership configuration
     *
     * @return  void
     * @since   1.0.0
     */
    private function updateUserGroups($userId, $membershipConfig)
    {
        $groupId = $membershipConfig['group_id'] ?? null;
        
        if (empty($groupId)) {
            return;
        }

        // Clear existing group assignments
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__user_usergroup_map'))
            ->where($this->db->quoteName('user_id') . ' = ' . (int) $userId);
        
        $this->db->setQuery($query)->execute();

        // Add new group assignment
        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__user_usergroup_map'))
            ->columns([$this->db->quoteName('user_id'), $this->db->quoteName('group_id')])
            ->values((int) $userId . ', ' . (int) $groupId);
        
        $this->db->setQuery($query)->execute();
    }

    /**
     * Record membership data
     *
     * @param   integer  $userId              The user ID
     * @param   array    $member              The member data
     * @param   array    $membershipConfig    The membership configuration
     *
     * @return  void
     * @since   1.0.0
     */
    private function recordMembershipData($userId, $member, $membershipConfig)
    {
        try {
            // Check if member record exists
            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__osmembership_members'))
                ->where($this->db->quoteName('user_id') . ' = ' . (int) $userId)
                ->setLimit(1);

            $recordId = $this->db->setQuery($query)->loadResult();

            $memberData = [
                'user_id' => $userId,
                'membership_type' => $member['membership_type'] ?? 'basic',
                'external_id' => $member['id'] ?? null,
                'status' => $member['status'] ?? 'active',
                'joined_date' => $member['joined_date'] ?? date('Y-m-d H:i:s'),
                'expiry_date' => $this->calculateExpiryDate($membershipConfig),
                'config_data' => json_encode($membershipConfig),
                'last_synced' => date('Y-m-d H:i:s'),
            ];

            if ($recordId) {
                // Update existing record
                $query = $this->db->getQuery(true)
                    ->update($this->db->quoteName('#__osmembership_members'))
                    ->where($this->db->quoteName('id') . ' = ' . (int) $recordId);

                foreach ($memberData as $column => $value) {
                    if ($column !== 'id' && $column !== 'user_id') {
                        $query->set($this->db->quoteName($column) . ' = ' . $this->db->quote($value));
                    }
                }

                $this->db->setQuery($query)->execute();
            } else {
                // Insert new record
                $query = $this->db->getQuery(true)
                    ->insert($this->db->quoteName('#__osmembership_members'))
                    ->columns($this->db->quoteName(array_keys($memberData)))
                    ->values(implode(',', array_map([$this->db, 'quote'], array_values($memberData))));

                $this->db->setQuery($query)->execute();
            }
        } catch (\Exception $e) {
            Log::add(
                'Failed to record membership data: ' . $e->getMessage(),
                Log::WARNING,
                'plg_system_osmembership'
            );
        }
    }

    /**
     * Calculate membership expiry date
     *
     * @param   array  $membershipConfig  The membership configuration
     *
     * @return  string
     * @since   1.0.0
     */
    private function calculateExpiryDate($membershipConfig)
    {
        $expiryDays = $membershipConfig['expiry_days'] ?? null;

        if (empty($expiryDays)) {
            return null;
        }

        $expiryDate = new \DateTime();
        $expiryDate->modify('+' . $expiryDays . ' days');
        
        return $expiryDate->format('Y-m-d H:i:s');
    }

    /**
     * Get HTTP headers for API requests
     *
     * @return  array
     * @since   1.0.0
     */
    private function getHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json'
        ];
    }
}
