<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bangpound\PhirehoseBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this->setName('phirehose:consume:basic');
        $this->setDescription('Consume Tweets from firehose');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // Start streaming

        /* @var $stream Bangpound\PhirehoseBundle\Stream\BasicStream  */
        $stream = $this->getContainer()->get('bangpound_phirehose.stream')
            ->setOutput($output);
        $stream->checkFilterPredicates();
        $stream->consume();
    }
}
