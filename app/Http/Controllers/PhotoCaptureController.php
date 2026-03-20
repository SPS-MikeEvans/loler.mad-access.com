<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PhotoCaptureController extends Controller
{
    public function show(string $token): View
    {
        $data = Cache::get('phone_photo:'.$token);
        abort_if($data === null, 404, 'This upload link has expired or is invalid.');

        return view('photo-capture.show', [
            'token' => $token,
            'checkText' => $data['check_text'] ?? '',
            'uploadUrl' => route('photo-capture.upload', $token),
        ]);
    }

    public function qrCode(string $token): Response
    {
        $data = Cache::get('phone_photo:'.$token);
        abort_if($data === null, 404);

        $svg = QrCode::format('svg')->size(180)->errorCorrection('M')
            ->generate(route('photo-capture.show', $token));

        return response($svg, 200)->header('Content-Type', 'image/svg+xml');
    }

    public function upload(Request $request, string $token): JsonResponse
    {
        $data = Cache::get('phone_photo:'.$token);
        abort_if($data === null, 404, 'This upload link has expired or is invalid.');

        $request->validate([
            'photo' => ['required', 'image', 'max:10240'],
        ]);

        $file = $request->file('photo');
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $path = 'inspection-photos/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        $data['photos'][] = $path;
        Cache::put('phone_photo:'.$token, $data, now()->addHours(2));

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function status(string $token): JsonResponse
    {
        $data = Cache::get('phone_photo:'.$token);
        abort_if($data === null, 404);

        $photos = array_map(
            fn (string $path) => ['url' => Storage::disk('public')->url($path)],
            $data['photos'] ?? []
        );

        return response()->json(['photos' => $photos]);
    }
}
