<?php
/**
 * Created by PhpStorm.
 * Date: 2016/06/25
 * Time: 9:33
 *
 * @author limi
 */

namespace Zeroleaf\Bj\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('make')
            ->setDescription('Make new article')
            ->addArgument(
                'name',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'The name of the file'
            )
            ->addOption(
                'draft',
                'd',
                InputOption::VALUE_NONE,
                'This article is draft rather than post'
            )
            ->addOption(
                'layout',
                'l',
                InputOption::VALUE_REQUIRED,
                'The layout of this article',
                'post'
            )
            ->addOption(
                'title',
                't',
                InputOption::VALUE_REQUIRED,
                'The title of this article'
            )
            ->addOption(
                'categories',
                'c',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The categories of this article',
                []
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->validateInput($input, $output);

        $data = [
            'layout'     => $input->getOption('layout'),
            'title'      => $this->getTitleFromInput($input),
            'categories' => $input->getOption('categories'),
            'date'       => date('Y-m-d H:i:s', time()),
        ];

        $filename = $this->getTargetFilenameFromInput($input);

        $this->writeToFile($filename, $data);
    }

    private function validateInput(InputInterface $input, OutputInterface $output)
    {
        $context = $this->getContextFromInput($input);

        $dir = "_{$context}s";
        if (! is_dir($dir)) {
            $output->writeln("<error>Target dir {$dir} not exist, make sure you are in the jekyll root directory</error>");
            exit(1);
        }
    }

    public function normalizeTitle($title)
    {
        return strtolower(preg_replace('/\\s+/', '-', $title));
    }

    private function getContextFromInput(InputInterface $input)
    {
        if ($input->getOption('draft')) {
            return 'draft';
        }

        return 'post';
    }

    private function getTargetFilenameFromInput(InputInterface $input)
    {
        $title = $this->normalizeTitle(implode(' ', $input->getArgument('name')));

        if ($input->getOption('draft')) {
            return "_drafts/$title";
        }

        return sprintf("_posts/%s-%s", date('Y-m-d', time()), $title);
    }

    private function getTitleFromInput(InputInterface $input)
    {
        if ($title = $input->getOption('title')) {
            return $title;
        }

        $name = implode(' ', $input->getArgument('name'));

        $capitalizeWords = array_filter(array_map(function ($word) {
            return $word ? ucfirst($word) : '';
        }, explode(' ', $name)));

        return implode(' ', $capitalizeWords);
    }

    private function writeToFile($filename, $data)
    {
        $template = __DIR__ . '/../../tpl/jekyll.stub';
        $content  = file_get_contents($template);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            $content = str_replace('$' . $key, $value, $content);
        }

        file_put_contents($filename, $content);
    }
}