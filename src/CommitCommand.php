<?php
declare(strict_types=1);
namespace Narrowspark\SecurityAdvisories;

use Viserio\Component\Console\Command\AbstractCommand;

class CommitCommand extends AbstractCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'commit';

    /**
     * {@inheritdoc}
     */
    protected $signature = 'commit';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Commit changes to narrowspark/security-advisories';

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        return 0;
    }
}
