<?php

namespace LaPoste\Colissimo\Model\Mail\Template;

use LaPoste\Colissimo\Logger\Colissimo;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Framework\Mail\AddressConverter;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\Part\AbstractMultipartPart;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\MixedPart;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    private const TYPE_OCTET_STREAM = 'application/octet-stream';
    private const TYPE_PDF = 'application/pdf';

    private array $attachments = [];
    protected Colissimo $logger;

    public function __construct(
        FactoryInterface              $templateFactory,
        MessageInterface              $message,
        SenderResolverInterface       $senderResolver,
        ObjectManagerInterface        $objectManager,
        TransportInterfaceFactory     $mailTransportFactory,
        Colissimo                     $logger,
        ?MessageInterfaceFactory      $messageFactory = null,
        ?EmailMessageInterfaceFactory $emailMessageInterfaceFactory = null,
        ?MimeMessageInterfaceFactory  $mimeMessageInterfaceFactory = null,
        ?MimePartInterfaceFactory     $mimePartInterfaceFactory = null,
        ?AddressConverter             $addressConverter = null
    ) {
        parent::__construct(
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory,
            $messageFactory,
            $emailMessageInterfaceFactory,
            $mimeMessageInterfaceFactory,
            $mimePartInterfaceFactory,
            $addressConverter
        );

        $this->logger = $logger;
    }

    public function addPdfAttachment(
        $body,
        $filename = null,
        $mimeType = self::TYPE_PDF
    ): static {
        return $this->addAttachment(
            $body,
            $mimeType,
            $filename
        );
    }

    public function addAttachment(
        string  $body,
        string  $mimeType = self::TYPE_OCTET_STREAM,
        ?string $filename = null
    ): static {
        $this->attachments[] = [
            'body' => $body,
            'mimeType' => $mimeType,
            'filename' => $filename,
        ];

        return $this;
    }

    protected function prepareMessage(): static
    {
        parent::prepareMessage();

        if (empty($this->attachments)) {
            return $this;
        }

        $symfonyMessage = $this->getSymfonyEmail();

        if ($symfonyMessage === null) {
            $this->logger->error(__METHOD__, ['error' => 'symfonyMessage is null, cannot attach']);

            return $this;
        }

        foreach ($this->attachments as $attachment) {
            if ($symfonyMessage instanceof Email) {
                $symfonyMessage->attach(
                    $attachment['body'],
                    $attachment['filename'],
                    $attachment['mimeType']
                );
            } else {
                $dataPart = new DataPart(
                    $attachment['body'],
                    $attachment['filename'],
                    $attachment['mimeType']
                );
                $body = $symfonyMessage->getBody();
                if ($body instanceof AbstractMultipartPart) {
                    $parts = array_merge($body->getParts(), [$dataPart]);
                    $symfonyMessage->setBody(new MixedPart(...$parts));
                } else {
                    $symfonyMessage->setBody(
                        new MixedPart(
                            ...array_filter([$body, $dataPart])
                        )
                    );
                }
            }
        }

        return $this;
    }

    private function getSymfonyEmail(): ?Message
    {
        try {
            $reflection = new \ReflectionProperty($this->message, 'symfonyMessage');
            $symfonyMessage = $reflection->getValue($this->message);

            return $symfonyMessage instanceof Message
                ? $symfonyMessage
                : null;
        } catch (\ReflectionException $e) {
            $this->logger->error(__METHOD__, ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function reset(): static
    {
        $this->attachments = [];

        return parent::reset();
    }
}
