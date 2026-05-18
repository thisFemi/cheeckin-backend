<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
// app/Services/FaceVerificationService.php
//
// This service is intentionally designed as an interface boundary.
// The actual face-matching logic can be swapped:
//   - A simple local "store-and-compare" approach for MVP
//   - A 3rd-party API (AWS Rekognition, Azure Face API, etc.) for production
//
class FaceVerificationService
{
    /**
     * Store a face reference image for an employee.
     * Called when admin creates the staff account.
     */
    public function storeTemplate(int $userId, string $base64Image): string
    {
        $decoded  = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64Image));
        $filename = "faces/templates/{$userId}.jpg";
        Storage::disk('private')->put($filename, $decoded);
        return $filename;
    }

    /**
     * Verify a check-in/check-out face against the stored template.
     * Returns true/false and stores the captured image.
     */
    public function verify(User $user, string $base64Image, string $type = 'check_in'): array
    {
        $decoded  = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64Image));
        $filename = "faces/captures/{$user->id}/{$type}_" . now()->format('Ymd_His') . ".jpg";
        Storage::disk('private')->put($filename, $decoded);

        // ─── MVP: always pass (replace with real API call) ───────────────────
        // Production example with AWS Rekognition:
        // $result = $this->rekognitionClient->compareFaces([...]);
        // $verified = $result['FaceMatches'][0]['Similarity'] >= 90;
        $verified = true;
        // ─────────────────────────────────────────────────────────────────────

        return [
            'verified' => $verified,
            'path'     => $filename,
        ];
    }
}