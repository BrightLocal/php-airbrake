<?php
namespace Airbrake;

use Airbrake\Interfaces\NotificationHandler;
use Exception;

/**
 * Airbrake client class.
 *
 * @package    Airbrake
 * @author     Drew Butler <drew@dbtlr.com>
 * @copyright  (c) 2011-2013 Drew Butler
 * @license    http://www.opensource.org/licenses/mit-license.php
 */
class Client {

    /** @var Configuration */
    protected $configuration = null;
    /** @var Connection|null */
    protected $connection = null;
    /** @var Notice|null */
    protected $notice = null;

    /**
     * Build the Client with the Airbrake Configuration.
     *
     * @throws \Airbrake\Exception
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration) {
        $configuration->verify();
        $this->configuration = $configuration;
        $this->connection = new Connection($configuration);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration() {
        return $this->configuration;
    }

    /**
     * Notify on an error message.
     *
     * @param string $message
     * @param array $backtrace
     *
     * @return string
     */
    public function notifyOnError($message, array $backtrace = null) {
        if (!$backtrace) {
            $backtrace = debug_backtrace();
            if (count($backtrace) > 1) {
                array_shift($backtrace);
            }
        }
        $notice = new Notice;
        $notice->load(array(
            'errorClass'   => 'PHP Error',
            'backtrace'    => $backtrace,
            'errorMessage' => $message,
        ));
        return $this->notify($notice);
    }

    /**
     * Notify on an exception
     *
     * @param Exception $exception
     *
     * @return string
     */
    public function notifyOnException(Exception $exception) {
        $notice = new Notice;
        $notice->load(array(
            'errorClass'   => get_class($exception),
            'backtrace'    => $this->cleanBacktrace($exception->getTrace() ?: debug_backtrace()),
            'errorMessage' => $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine(),
        ));
        return $this->notify($notice);
    }

    /**
     * Notify about the notice.
     * If there is a notify handler instance supplied, it will be used to handle notification sending.
     *
     * @param \Airbrake\Notice $notice
     *
     * @return string
     */
    public function notify(Notice $notice) {
        if (!empty($this->configuration->notifyHandler) && $this->configuration->notifyHandler instanceof NotificationHandler) {
            return $this->configuration->notifyHandler->sendNotification($notice);
        } else {
            return $this->connection->send($notice);
        }
    }

    /**
     * Clean the backtrace of unneeded junk.
     *
     * @param array $backtrace
     *
     * @return array
     */
    protected function cleanBacktrace($backtrace) {
        foreach ($backtrace as &$item) {
            unset($item['args']);
        }
        return $backtrace;
    }
}
