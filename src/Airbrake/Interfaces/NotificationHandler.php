<?php
namespace Airbrake\Interfaces;

use Airbrake\Notice;

/**
 * @author Dmitry Vovk <dmitry@brightlocal.com>
 */
interface NotificationHandler {

    public function sendNotification(Notice $notice);
}
