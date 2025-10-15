<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OtpService
{
    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 10;

    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    public function sendOtpEmail(User $user, string $otp): void
    {
        $email = (new Email())
            ->from('noreply@ecommerce-livres.com')
            ->to($user->getEmail())
            ->subject('Code de vérification - E-commerce Livres Informatiques')
            ->html($this->generateOtpEmailTemplate($user, $otp));

        $this->mailer->send($email);
    }

    private function generateOtpEmailTemplate(User $user, string $otp): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f8f9fa; }
                .otp-code { font-size: 32px; font-weight: bold; text-align: center; color: #007bff; padding: 20px; background-color: white; border: 2px solid #007bff; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📚 E-commerce Livres Informatiques</h1>
                    <p>Code de vérification à deux facteurs</p>
                </div>
                
                <div class='content'>
                    <h2>Bonjour {$user->getFirstName()},</h2>
                    
                    <p>Vous avez demandé un code de vérification pour accéder à votre compte.</p>
                    
                    <p>Votre code de vérification est :</p>
                    
                    <div class='otp-code'>{$otp}</div>
                    
                    <p><strong>Important :</strong></p>
                    <ul>
                        <li>Ce code expire dans " . self::OTP_EXPIRY_MINUTES . " minutes</li>
                        <li>Ne partagez jamais ce code avec qui que ce soit</li>
                        <li>Si vous n'avez pas demandé ce code, ignorez cet email</li>
                    </ul>
                    
                    <p>Si vous rencontrez des problèmes, contactez notre support.</p>
                </div>
                
                <div class='footer'>
                    <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                    <p>&copy; 2025 E-commerce Livres Informatiques - Tous droits réservés</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    public function isOtpExpired(int $otpTimestamp): bool
    {
        $expiryTime = $otpTimestamp + (self::OTP_EXPIRY_MINUTES * 60);
        return time() > $expiryTime;
    }
}
