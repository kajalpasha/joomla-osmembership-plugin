<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Osmembership
 *
 * @copyright   (C) 2024 Your Company
 * @license     GNU General Public License version 2 or later
 */

namespace Kajalpasha\Plugin\System\Osmembership\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Dispatcher\Dispatcher;
use Joomla\CMS\Application\ApplicationInterface;
use Joomla\CMS\Log\Log;
use Joomla\Event\DispatcherInterface;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Kajalpasha\Plugin\System\Osmembership\Service\OsmembershipService;
use Kajalpasha\Plugin\System\Osmembership\Service\MembershipTypeHandler;

/**
 * OSMembership System Plugin
 *
 * Integrates OS Membership with Joomla 4 for automatic user sync,
 * membership management, and access control.
 *
 * @since  1.0.0
 */
class Osmembership extends CMSPlugin
{
    /**
     * Autload plugin languages
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * OS Membership service
     *
     * @var    OsmembershipService
     * @since  1.0.0
     */
    private $osmembershipService;

    /**
     * Membership Type Handler
     *
     * @var    MembershipTypeHandler
     * @since  1.0.0
     */
    private $membershipTypeHandler;

    /**
     * Database driver
     *
     * @var    DatabaseInterface
     * @since  1.0.0
     */
    private $db;

    /**
     * Constructor
     *
     * @param   DispatcherInterface  $dispatcher  The dispatcher
     * @param   array                $config      The plugin configuration
     * @param   DatabaseInterface    $db          The database driver
     *
     * @since   1.0.0
     */
    public function __construct(DispatcherInterface $dispatcher, array $config, DatabaseInterface $db = null)
    {
        parent::__construct($dispatcher, $config);
        
        $this->db = $db ?: Factory::getContainer()->get(DatabaseInterface::class);
    }

    /**
     * Called when application initializes
     *
     * @return  void
     * @since   1.0.0
     */
    public function onAfterInitialise()
    {
        try {
            $this->initializeServices();
            
            // Check if sync is enabled
            if ($this->params->get('enable_sync', 1)) {
                $this->performSync();
            }
        } catch (\Exception $e) {
            Log::add(
                'OSMembership Plugin Error: ' . $e->getMessage(),
                Log::ERROR,
                'plg_system_osmembership'
            );
        }
    }

    /**
     * Initialize all services
     *
     * @return  void
     * @since   1.0.0
     */
    private function initializeServices()
    {
        if ($this->osmembershipService === null) {
            $this->osmembershipService = new OsmembershipService(
                $this->params->get('api_key'),
                $this->params->get('api_url'),
                $this->db
            );
        }

        if ($this->membershipTypeHandler === null) {
            $this->membershipTypeHandler = new MembershipTypeHandler($this->db);
        }
    }

    /**
     * Perform membership sync
     *
     * @return  void
     * @since   1.0.0
     */
    private function performSync()
    {
        // Check if sync is needed based on interval
        $lastSync = $this->getLastSyncTime();
        $syncInterval = (int) $this->params->get('sync_interval', 3600);
        $currentTime = time();

        if ($currentTime - $lastSync < $syncInterval) {
            return;
        }

        try {
            $this->osmembershipService->syncMembers();
            $this->setLastSyncTime($currentTime);
            
            Log::add(
                'OSMembership sync completed successfully',
                Log::INFO,
                'plg_system_osmembership'
            );
        } catch (\Exception $e) {
            Log::add(
                'OSMembership sync failed: ' . $e->getMessage(),
                Log::WARNING,
                'plg_system_osmembership'
            );
        }
    }

    /**
     * Get last sync timestamp
     *
     * @return  integer
     * @since   1.0.0
     */
    private function getLastSyncTime()
    {
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('value'))
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('osmembership'))
            ->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'));
        
        try {
            $result = $this->db->setQuery($query)->loadResult();
            $data = json_decode($result, true);
            return isset($data['last_sync']) ? (int) $data['last_sync'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Set last sync timestamp
     *
     * @param   integer  $timestamp  The timestamp
     *
     * @return  void
     * @since   1.0.0
     */
    private function setLastSyncTime($timestamp)
    {
        // Implementation for storing last sync time
        // This would typically be stored in the plugin's data or a custom table
    }

    /**
     * Public method to process membership by type
     * 
     * This can be called from other components
     *
     * @param   array  $member  The member data
     *
     * @return  array
     * @throws  \Exception
     * @since   1.0.0
     */
    public function processMembershipByType($member)
    {
        $this->initializeServices();
        return $this->membershipTypeHandler->processMembershipByType($member);
    }

    /**
     * Public method to get membership types
     *
     * @return  array
     * @since   1.0.0
     */
    public function getMembershipTypes()
    {
        $this->initializeServices();
        return $this->membershipTypeHandler->getMembershipTypes();
    }

    /**
     * Public method to get membership type by ID
     *
     * @param   integer  $typeId  The membership type ID
     *
     * @return  array|null
     * @since   1.0.0
     */
    public function getMembershipTypeById($typeId)
    {
        $this->initializeServices();
        return $this->membershipTypeHandler->getMembershipTypeById($typeId);
    }

    /**
     * Public method to get membership type by slug
     *
     * @param   string  $slug  The membership type slug
     *
     * @return  array|null
     * @since   1.0.0
     */
    public function getMembershipTypeBySlug($slug)
    {
        $this->initializeServices();
        return $this->membershipTypeHandler->getMembershipTypeBySlug($slug);
    }
}
