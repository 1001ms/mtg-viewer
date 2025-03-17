<?php

namespace App\Command;

use App\Entity\Artist;
use App\Entity\Card;
use App\Repository\ArtistRepository;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'import:card',
    description: 'Import cards from CSV',
)]
class ImportCardCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface        $logger,
        private array                           $csvHeader = []
    ) 
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '2G');
        $io = new SymfonyStyle($input, $output);
        $filepath = __DIR__ . '/../../data/cards.csv';
        
        if (!file_exists($filepath)) {
            $this->logger->error('File not found: ' . $filepath);
            $io->error('File not found');
            return Command::FAILURE;
        }
    
        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            $this->logger->error('Cannot open file: ' . $filepath);
            $io->error('Cannot open file');
            return Command::FAILURE;
        }
        
        $start = microtime(true);
        $this->logger->info('Starting card import from ' . $filepath);
    
        $this->csvHeader = fgetcsv($handle);
        $uuidInDatabase = array_flip($this->entityManager->getRepository(Card::class)->getAllUuids());
    
        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('Importing cards...');
    
        $batchSize = 10000;
        $i = 0;
        
        $this->entityManager->beginTransaction();
    
        while (($row = $this->readCSV($handle)) !== false) {
            if (!isset($uuidInDatabase[$row['uuid']])) {
                $this->addCard($row);
                $this->logger->info('Card added: ' . $row['uuid']);
            }
    
            if (++$i % $batchSize === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $progressIndicator->advance();
                $this->logger->info('Flushed and cleared after processing ' . $i . ' cards');
            }
    
            if ($i >= $batchSize) {
                $this->logger->info("Reached limit of $batchSize cards, stopping import.");
                break;
            }
        }
    
        $this->entityManager->flush();
        $this->entityManager->clear();
        $this->entityManager->commit();
        $this->logger->info('Final flush after importing ' . $i . ' cards');
        $progressIndicator->finish('Importing cards done.');
    
        fclose($handle);
    
        $end = microtime(true);
        $timeElapsed = $end - $start;
        $this->logger->info(sprintf('Import completed: %d cards in %.2f seconds', $i, $timeElapsed));
        $io->success(sprintf('Imported %d cards in %.2f seconds', $i, $timeElapsed));
        
        return Command::SUCCESS;
    }    

    private function readCSV(mixed $handle): array|false
    {
        $row = fgetcsv($handle);
        if ($row === false) {
            return false;
        }
        return array_combine($this->csvHeader, $row);
    }

    private function addCard(array $row): void
    {
        try {
            $uuid = $row['uuid'];

            $card = new Card();
            $card->setUuid($uuid);
            $card->setManaValue($row['manaValue']);
            $card->setManaCost($row['manaCost']);
            $card->setName($row['name']);
            $card->setRarity($row['rarity']);
            $card->setSetCode($row['setCode']);
            $card->setSubtype($row['subtypes']);
            $card->setText($row['text']);
            $card->setType($row['type']);
            
            $this->entityManager->persist($card);
        } catch (\Exception $e) {
            $this->logger->error('Error while adding card: ' . $e->getMessage());
        }
    }
}