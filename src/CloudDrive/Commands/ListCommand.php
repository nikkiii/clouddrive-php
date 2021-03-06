<?php
/**
 * @author Alex Phillips <ahp118@gmail.com>
 * Date: 7/11/15
 * Time: 3:22 PM
 */

namespace CloudDrive\Commands;

use CloudDrive\Node;
use Symfony\Component\Console\Input\InputArgument;

class ListCommand extends Command
{
    protected $onlineCommand = false;

    protected function configure()
    {
        $this->setName('ls')
            ->setDescription('List all remote nodes inside of a specified directory')
            ->addArgument('remote_path', InputArgument::OPTIONAL, 'The remote directory to list')
            ->addOption('time', 't', null, 'Order output by date modified')
            ->addOption('id', 'i', null, 'Designate the remote node by its ID instead of its remote path')
            ->addOption('assets', 'a', null, "List node's assets if requesting a FILE node");
    }

    protected function main()
    {
        $this->init();
        $this->clouddrive->getAccount()->authorize();

        $remotePath = $this->input->getArgument('remote_path') ?: '';

        $sort = Command::SORT_BY_NAME;
        if ($this->input->getOption('time')) {
            $sort = Command::SORT_BY_TIME;
        }

        if ($this->input->getOption('id')) {
            if (!($node = Node::loadById($remotePath))) {
                throw new \Exception("No node exists with ID '$remotePath'.");
            }
        } else {
            if (!($node = Node::loadByPath($remotePath))) {
                throw new \Exception("No node exists at remote path '$remotePath'.");
            }
        }

        if ($node->isFolder() || $this->input->getOption('assets')) {
            $this->listNodes($node->getChildren(), $sort);
        } else {
            $this->listNodes([$node], $sort);
        }
    }
}
