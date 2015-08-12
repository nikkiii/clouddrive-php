<?php
/**
 * @author Alex Phillips <ahp118@gmail.com>
 * Date: 8/6/15
 * Time: 1:30 PM
 */

namespace CloudDrive\Commands;

use CloudDrive\Node;

class FindCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('find')
            ->setDescription('Find nodes by name or MD5 checksum')
            ->addArgument('query')
            ->addOption('md5', null, null, 'Search for nodes by MD5 rather than name')
            ->addOption('time', 't', null, 'Order output by date modified');
    }

    protected function main()
    {
        $this->init();

        $query = $this->input->getArgument('query');
        if ($this->input->getOption('md5')) {
            $nodes = [Node::findNodeByMd5($query)];
        } else {
            $nodes = Node::searchNodesByName($query);
        }

        $sort = BaseCommand::SORT_BY_NAME;
        if ($this->input->getOption('time')) {
            $sort = BaseCommand::SORT_BY_TIME;
        }

        $this->listNodes($nodes, $sort);
    }
}