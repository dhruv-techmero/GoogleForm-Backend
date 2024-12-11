<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class ApiController extends Controller
{
    public function uploadImage(Request $request)
    {
        // Validate the request
        $request->validate([
            'image' => 'required|string',
        ]);

        // Decode the base64 image
        $imageData = $request->input('image');
        $image = str_replace('data:image/jpeg;base64,', '', $imageData);
        $image = str_replace(' ', '+', $image);
        $imagePath = 'images/' . uniqid() . '.jpeg';
        \Storage::disk('local')->put($imagePath, base64_decode($image));

        // Prepare the Bunny CDN upload
        $apiKey = '6c86dcd8-9f3d-4386-879ef8ba94e4-6d37-48b2';
        $storageZoneName = 'google-forms-app'; // Replace with your storage zone name
        $hostname = 'https://ny.storage.bunnycdn.com'; // Use the provided hostname
        $filename = basename($imagePath); // Get the filename from the stored path

        // Create a Guzzle client
        $client = new Client();

        try {
            // Use Guzzle to upload the image to Bunny CDN
            $response = $client->put("$hostname/$storageZoneName/$filename", [
                'headers' => [
                    'AccessKey' => $apiKey, // Use AccessKey for authorization
                    'Content-Type' => 'application/octet-stream', // Set content type
                    'accept' => 'application/json', // Accept JSON response
                ],
                'body' => fopen(storage_path("app/$imagePath"), 'r'), // Stream the file
            ]);

            // Construct the URL of the uploaded image using the provided base URL
            $baseUrl = 'https://googleform.b-cdn.net/';
            $imageUrl = $baseUrl . basename($imagePath);

            // Return response with image URL
            return response()->json(['message' => 'Image uploaded successfully', 'image_url' => $imageUrl, 'data' => json_decode($response->getBody())], 200);
        } catch (\Exception $e) {
            // Handle any errors
            return response()->json(['message' => 'Image upload failed', 'error' => $e->getMessage()], 500);
        }
    }
}
