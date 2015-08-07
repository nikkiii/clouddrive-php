<?php
/**
 * @author Alex Phillips <ahp118@gmail.com>
 * Date: 7/10/15
 * Time: 5:36 PM
 */

namespace CloudDrive\Commands;

use Cilex\Command\Command;
use CloudDrive\Cache\SQLite;
use CloudDrive\CloudDrive;
use CloudDrive\Node;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\ParameterBag;

abstract class BaseCommand extends Command
{
    /**
     * @var \CloudDrive\Cache
     */
    protected $cacheStore;

    /**
     * @var \CloudDrive\CloudDrive
     */
    protected $clouddrive;

    /**
     * @var \Utility\ParameterBag
     */
    protected $config;

    /**
     * @var string
     */
    protected $configFile;

    /**
     * @var string
     */
    protected $configPath;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    protected function convertFilesize($bytes, $decimals = 2)
    {
        $bytes = $bytes ?: 0;
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $home = getenv('HOME');
        if (!$home) {
            throw new \RuntimeException("'HOME' environment variable must be set for Cloud Drive to properly run.");
        }

        $this->configPath = rtrim($home, '/') . '/.cache/clouddrive-php/';
        if (!file_exists($this->configPath)) {
            mkdir($this->configPath, 0777, true);
        }

        $this->configFile = "{$this->configPath}config.json";

        $this->input = $input;
        $this->output = $output;

        // Set up basic styling
        $this->output->getFormatter()->setStyle('folder', new OutputFormatterStyle('blue'));

        $this->main();
    }

    /**
     * @throws \Exception
     */
    protected function init()
    {
        $this->readConfig();
        if (count($this->config) === 0) {
            throw new \Exception('Account has not been authorized. Please do so using the `init` command.');
        }

        $this->cacheStore = new SQLite($this->config['email'], $this->configPath);

        if ($this->config['email'] && $this->config['client-id'] && $this->config['client-secret']) {
            $clouddrive = new CloudDrive(
                $this->config['email'],
                $this->config['client-id'],
                $this->config['client-secret'],
                $this->cacheStore
            );

            if ($clouddrive->getAccount()->authorize()['success']) {
                $this->clouddrive = $clouddrive;
                Node::init($this->clouddrive->getAccount(), $this->cacheStore);
            } else {
                throw new \Exception('Account has not been authorized. Please do so using the `init` command.');
            }
        }
    }

    abstract protected function main();

    protected function listNodes(array $nodes)
    {
        foreach ($nodes as $node) {
            $modified = new \DateTime($node['modifiedDate']);
            $name = $node['kind'] === 'FOLDER' ? "<folder>{$node['name']}</folder>" : $node['name'];
            $this->output->writeln(
                sprintf(
                    "%s  %s  %s %s %s %s",
                    $node['id'],
                    $modified->format("M d y H:m"),
                    str_pad($node['status'], 10),
                    str_pad($node['kind'], 7),
                    str_pad($this->convertFilesize($node['contentProperties']['size']), 9),
                    $name
                )
            );
        }
    }

    /**
     *
     */
    protected function readConfig()
    {
        if (file_exists($this->configFile) && $data = json_decode(file_get_contents($this->configFile), true)) {
            $this->config = new ParameterBag($data);
        } else {
            $this->config = new ParameterBag();
        }
    }

    /**
     *
     */
    protected function saveConfig()
    {
        file_put_contents("{$this->configPath}cli.json", json_encode($this->config));
    }
}
