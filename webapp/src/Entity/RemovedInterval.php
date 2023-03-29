<?php declare(strict_types=1);

namespace App\Entity;

use App\Utils\Utils;
use App\Validator\Constraints\TimeString;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Time intervals removed from the contest for scoring.
 */
#[ORM\Table(
    name: 'removed_interval',
    options: [
        'collation' => 'utf8mb4_unicode_ci',
        'charset' => 'utf8mb4',
        'comment' => 'Time intervals removed from the contest for scoring',
    ])]
#[ORM\Index(columns: ['cid'], name: 'cid')]
#[ORM\Entity]
class RemovedInterval
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(
        name: 'intervalid',
        type: 'integer',
        length: 4,
        nullable: false,
        options: ['comment' => 'Removed interval ID', 'unsigned' => true]
    )]
    private ?int $intervalid = null;

    /**
     * @var double|string
     */
    #[ORM\Column(
        name: 'starttime',
        type: 'decimal',
        precision: 32,
        scale: 9,
        nullable: false,
        options: ['comment' => 'Initial time of removed interval', 'unsigned' => true]
    )]
    private string|float $starttime;

    /**
     * @var double|string
     */
    #[ORM\Column(
        name: 'endtime',
        type: 'decimal',
        precision: 32,
        scale: 9,
        nullable: false,
        options: ['comment' => 'Final time of removed interval', 'unsigned' => true]
    )]
    private string|float $endtime;

    /**
     * @TimeString(allowRelative=false)
     */
    #[ORM\Column(
        name: 'starttime_string',
        type: 'string',
        length: 64,
        nullable: false,
        options: ['comment' => 'Authoritative (absolute only) string representation of starttime']
    )]
    private string $starttimeString;

    /**
     * @TimeString(allowRelative=false)
     */
    #[ORM\Column(
        name: 'endtime_string',
        type: 'string',
        length: 64,
        nullable: false,
        options: ['comment' => 'Authoritative (absolute only) string representation of endtime']
    )]
    private string $endtimeString;

    #[ORM\ManyToOne(targetEntity: Contest::class, inversedBy: 'removedIntervals')]
    #[ORM\JoinColumn(name: 'cid', referencedColumnName: 'cid', onDelete: 'CASCADE')]
    private Contest $contest;

    public function getIntervalid(): ?int
    {
        return $this->intervalid;
    }

    public function setStarttime(string|float $starttime): RemovedInterval
    {
        $this->starttime = $starttime;
        return $this;
    }

    public function getStarttime(): string|float
    {
        return $this->starttime;
    }

    public function setEndtime(string|float $endtime): RemovedInterval
    {
        $this->endtime = $endtime;
        return $this;
    }

    public function getEndtime(): string|float
    {
        return $this->endtime;
    }

    /**
     * @throws Exception
     */
    public function setStarttimeString(string $starttimeString): RemovedInterval
    {
        $this->starttimeString = $starttimeString;
        $date                  = new DateTime($starttimeString);
        $this->starttime       = $date->format('U.v');

        return $this;
    }

    public function getStarttimeString(): ?string
    {
        return $this->starttimeString;
    }

    /**
     * @throws Exception
     */
    public function setEndtimeString(string $endtimeString): RemovedInterval
    {
        $this->endtimeString = $endtimeString;
        $date                = new DateTime($endtimeString);
        $this->endtime       = $date->format('U.v');

        return $this;
    }

    public function getEndtimeString(): ?string
    {
        return $this->endtimeString;
    }

    public function setContest(?Contest $contest = null): RemovedInterval
    {
        $this->contest = $contest;
        return $this;
    }

    public function getContest(): Contest
    {
        return $this->contest;
    }

    /**
     * @Assert\Callback()
     */
    public function validate(ExecutionContextInterface $context)
    {
        // Update all contest timing, taking into account all removed intervals
        $this->getContest()->setStarttimeString($this->getContest()->getStarttimeString());

        if ($this->getEndtime() <= $this->getStarttime()) {
            $context
                ->buildViolation('Interval ends before (or when) it starts')
                ->atPath('starttimeString')
                ->addViolation();

            $context
                ->buildViolation('Interval ends before (or when) it starts')
                ->atPath('endtimeString')
                ->addViolation();
        }

        if (Utils::difftime((float)$this->getStarttime(), (float)$this->getContest()->getStarttime(true)) < 0) {
            $context
                ->buildViolation('Interval starttime outside of contest')
                ->atPath('starttimeString')
                ->addViolation();
        }
        if (Utils::difftime((float)$this->getEndtime(), (float)$this->getContest()->getEndtime()) > 0) {
            $context
                ->buildViolation('Interval endtime outside of contest')
                ->atPath('endtimeString')
                ->addViolation();
        }

        /** @var RemovedInterval $removedInterval */
        foreach ($this->getContest()->getRemovedIntervals() as $removedInterval) {
            if ($removedInterval->getIntervalid() === $this->getIntervalid()) {
                continue;
            }

            if ((Utils::difftime((float)$this->getStarttime(), (float)$removedInterval->getStarttime()) >= 0 &&
                    Utils::difftime((float)$this->getStarttime(), (float)$removedInterval->getEndtime()) < 0) ||
                (Utils::difftime((float)$this->getEndtime(), (float)$removedInterval->getStarttime()) > 0 &&
                    Utils::difftime((float)$this->getEndtime(), (float)$removedInterval->getEndtime()) <= 0)) {
                $context
                    ->buildViolation(sprintf('Interval overlaps with interval %d', $removedInterval->getIntervalid()))
                    ->atPath('starttimeString')
                    ->addViolation();

                $context
                    ->buildViolation(sprintf('Interval overlaps with interval %d', $removedInterval->getIntervalid()))
                    ->atPath('endtimeString')
                    ->addViolation();
            }
        }
    }
}
