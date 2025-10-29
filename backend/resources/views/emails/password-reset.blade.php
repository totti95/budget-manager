<!DOCTYPE html>
<html>
<head>
    <title>Réinitialisation de mot de passe</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #3B82F6; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0;">Réinitialisation de mot de passe</h1>
    </div>
    
    <div style="background-color: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px;">
        <p>Bonjour <strong>{{ $user->name }}</strong>,</p>

        <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}"
               style="background-color: #3B82F6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Réinitialiser mon mot de passe
            </a>
        </p>

        <p style="background-color: #FEF3C7; border-left: 4px solid #F59E0B; padding: 15px; margin: 20px 0;">
            <strong>Important :</strong> Ce lien est valable pendant 60 minutes.
        </p>

        <p style="font-size: 12px; color: #666;">
            Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.
        </p>

        <p style="font-size: 12px; color: #666;">
            Lien direct : {{ $resetUrl }}
        </p>
    </div>
</body>
</html>
