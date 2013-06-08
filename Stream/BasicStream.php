<?php

namespace Bangpound\PhirehoseBundle\Stream;

use Doctrine\Common\Persistence\ObjectManager;
use OauthPhirehose as OauthPhirehose;
use Sonata\NotificationBundle\Backend\BackendInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BasicStream extends OauthPhirehose implements ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     *
     * @api
     */
    protected $container;
    protected $backend;
    protected $output;
    protected $em;
    protected $lastMemoryUsage;

    /**
     * Sets the Container associated with this Controller.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        return $this;
    }

    public function setBackend(BackendInterface $backend)
    {
        $this->backend = $backend;
        return $this;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    public function setEntityManager(ObjectManager $em)
    {
        $this->em = $em;
        return $this;
    }

    /**
     * Enqueue each status
     *
     * @param string $status
     */
    public function enqueueStatus($status)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $body = json_decode($status, true, 512, JSON_BIGINT_AS_STRING);
        }
        else {
            $body = json_decode($status, true, 512);
        }
        // see https://dev.twitter.com/docs/streaming-apis/messages#Public_stream_messages
        //$types = array('delete', 'scrub_geo', 'limit', 'status_withheld', 'user_withheld', 'disconnect', 'warning');
        if (count($body) > 1) {
            $type = 'tweet';
            $body = array('tweet' => trim($status));
        }
        else {
            $type = 'tweet__'. key($body);
            $body = array(key($body) => trim($status));
        }
        $this->backend->createAndPublish($type, $body);
    }

    /**
     * Called every $this->avgPeriod (default=60) seconds, and this default implementation
     * calculates some rates, logs them, and resets the counters.
     */
    public function heartbeat()
    {
        $memory_usage = memory_get_usage();
        $message = 'Memory usage: ' . $this->formatMemory($memory_usage) .', ';
        if ($this->lastMemoryUsage)
        {
            if ($memory_usage > $this->lastMemoryUsage) {
                $message .= 'Δ <info>'. $this->formatMemory($memory_usage - $this->lastMemoryUsage) .'</info>, ';
            }
            else
            {
                $message .= 'Δ '. $this->formatMemory($memory_usage - $this->lastMemoryUsage) .', ';
            }
        }

        $message .= 'Peak: '. $this->formatMemory(memory_get_peak_usage(TRUE));
        $this->log($message);
        $this->lastMemoryUsage = $memory_usage;
        $this->em->flush();
        $this->em->clear();
        parent::heartbeat();
    }

    private function formatMemory($memory)
    {
        $memory = (int) $memory;
        if (abs($memory) < 1024) {
            return $memory." B";
        } elseif (abs($memory) < 1048576) {
            return round($memory / 1024, 2)." KB";
        } else {
            return round($memory / 1048576, 2)." MB";
        }
    }

    /**
     * Basic log function that outputs logging to the standard error_log() handler. This should generally be overridden
     * to suit the application environment.
     *
     * @param $messages
     * @param $level 'error', 'info', 'notice'. Defaults to 'notice', so you should set this
     *     parameter on the more important error messages.
     *     'info' is used for problems that the class should be able to recover from automatically.
     *     'error' is for exceptional conditions that may need human intervention. (For instance, emailing
     *          them to a system administrator may make sense.)
     */
    protected function log($message, $level = 'notice')
    {
        if ($level == 'notice')
        {
          $this->output->writeln(sprintf('%s', trim($message)));
        }
        else
        {
          $this->output->writeln(sprintf('<%s>%s</%s>', $level, trim($message), $level));
        }
    }
}
