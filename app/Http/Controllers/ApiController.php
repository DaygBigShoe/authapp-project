<?php

namespace App\Http\Controllers;

use App\Models\Client_user_auth;
use App\Models\User;
use Carbon\Carbon;
use chillerlan\QRCode\QRCode;
use Jenssegers\Agent\Agent;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;

use Illuminate\Http\Request;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\HiddenString\HiddenString;

class ApiController extends Controller
{

    public function account() {
        $user = User::where(["id" => auth()->user()->id])->first();
        if ($user) {
            return response()->json(["data" => ["email" => $user->email]]);
        }else {
            return response()->json(["Invalid user", 400]);
        }
    }

    public function users() {
        $users = Client_user_auth::where(["user_id" => auth()->user()->id])->orderBy("created_at", "DESC")->get();
        return response()->json(["data" => $users]);
    }

    public function register_user(Request $req)
    {
        $req->validate([
            "client_user_unique_identifier" => "string|required"
        ]);

        $encryptionKey = KeyFactory::loadEncryptionKey('../enckey.key');
        $data = new HiddenString(json_encode([
            "user_id" => auth()->user()->id,
            "client_user_unique_identifier" => $req->client_user_unique_identifier,
            "creation_date" => time()
        ]));

        $data = Symmetric::encrypt($data, $encryptionKey);

        $qr = (new QRCode)->render(env("APP_URL")."/"."register-user/validate"."/".$data);

        return response()->json(["data" => ["qr" => $qr, "key" => $data]]);
    }

    public function register_user_pin_validation(Request $req)
    {
        $req->validate([
            "pin" => "string|required",
            "client_user_unique_identifier" => "string|required"
        ]);

        $getClientUserAuth = Client_user_auth::where([
            "user_id" => auth()->user()->id,
            "client_user_unique_identifier" => $req->client_user_unique_identifier,
            "pin" => $req->pin
        ]);

        if ($getClientUserAuth->first() !== null) {
            $auth_key = $getClientUserAuth->first()->auth_key;
            $getClientUserAuth->update([
                "pin" => null
            ]);
            return response()->json(["data" => ["auth_key" => $auth_key]]);
        } else {
            return response()->json("invalid pin", 400);
        }
    }

    public function register_user_validate(Request $req, $data)
    {
        try {
            $encryptionKey = KeyFactory::loadEncryptionKey('../enckey.key');
            $decryptedData = Symmetric::decrypt($data, $encryptionKey)->getString();
        } catch (\Throwable $th) {
            $decryptedData = "";
        }

        $agent = new Agent();
        $browser = $agent->browser();
        $device = $agent->device();
        $platform = $agent->platform();
        $is_desktop = $agent->isDesktop();
        $is_expired = false;

        if (!$is_desktop && $decryptedData != "") {

            $dataObj = json_decode(($decryptedData));

            if (isset($dataObj->creation_date)) {
                if ($this->isExpired($dataObj->creation_date, 300)) {
                    $is_expired = true;
                }

                $sessionPayload = [
                    "user_id" => $dataObj->user_id,
                    "client_user_unique_identifier" => $dataObj->client_user_unique_identifier,
                    // "creation_date" => $dataObj->creation_date,
                    "browser" => $browser,
                    "platform" => $platform,
                    "device" => $device
                ];

                session(["register-payload" => json_encode($sessionPayload)]);
            }else {
                $decryptedData = "";
            }
        }

        $returnData = [
            "browser" => $browser,
            "platform" => $platform,
            "device" => $device,
            "is_desktop" => $is_desktop,
            "is_Invalid" => $decryptedData == "" ? true : false,
            "is_expired" => $is_expired
        ];

        return view("register_user_validate")->with($returnData);
    }

    public function register_retrieve_pin(Request $req)
    {
        $req->validate([
            "visitor_id" => "string|required"
        ]);
        if (!$req->ajax()) return response()->json("Invalid request");

        $sessionPayload = json_decode(session("register-payload"));

        $encryptionKey = KeyFactory::loadEncryptionKey('../enckey.key');
        $data = new HiddenString(json_encode([
            "user_id" => $sessionPayload->user_id,
            "client_user_unique_identifier" => $sessionPayload->client_user_unique_identifier,
            // "creation_date" => $sessionPayload->creation_date,
            "browser" => $sessionPayload->browser,
            "platform" => $sessionPayload->platform,
            "device" => $sessionPayload->device,
            "visitor_id" => $req->visitor_id
        ]));

        $data = Symmetric::encrypt($data, $encryptionKey);

        $pin = $this->generateRandomPIN(6);

        $getClientUserAuth = Client_user_auth::where(["user_id" => $sessionPayload->user_id, "client_user_unique_identifier" => $sessionPayload->client_user_unique_identifier]);
        if ($getClientUserAuth->first() == null) {
            Client_user_auth::create([
                "user_id" => $sessionPayload->user_id,
                "client_user_unique_identifier" => $sessionPayload->client_user_unique_identifier,
                "auth_key" => $data,
                "pin" => $pin
            ]);
        } else {
            $getClientUserAuth->update([
                "auth_key" => $data,
                "pin" => $pin
            ]);
        }

        return response()->json(["pin" => $pin]);
    }

    public function login_user(Request $req)
    {
        $req->validate([
            "client_user_unique_identifier" => "string|required",
            "long" => "required",
            "lat" => "required",
            "expires_in" => "required|max:4",
        ]);

        $encryptionKey = KeyFactory::loadEncryptionKey('../enckey.key');
        $data = new HiddenString(json_encode([
            "user_id" => auth()->user()->id,
            "client_user_unique_identifier" => $req->client_user_unique_identifier,
            "long" => $req->long,
            "lat" => $req->lat,
            "expires_in" => $req->expires_in,
            "creation_date" => time()
        ]));

        $data = Symmetric::encrypt($data, $encryptionKey);

        $qr = (new QRCode)->render(env("APP_URL")."/"."login-user/validate"."/".$data);

        return response()->json(["data" => ["qr" => $qr, "key" => $data]]);
    }

    public function login_user_pin_validation(Request $req)
    {
        $req->validate([
            "pin" => "string|required",
            "client_user_unique_identifier" => "string|required"
        ]);

        $getClientUserAuth = Client_user_auth::where([
            "user_id" => auth()->user()->id,
            "client_user_unique_identifier" => $req->client_user_unique_identifier,
            "pin" => $req->pin
        ]);

        if ($getClientUserAuth->first() !== null) {
            // $auth_key = $getClientUserAuth->first()->auth_key;
            $getClientUserAuth->update([
                "pin" => null
            ]);
            return response()->json(["data" => "successful"]);
        } else {
            return response()->json("invalid pin", 400);
        }
    }

    public function login_user_validate(Request $req, $data)
    {
        $isInvalid = false;
        try {
            $encryptionKey = KeyFactory::loadEncryptionKey('../enckey.key');
            $decryptedData = Symmetric::decrypt($data, $encryptionKey)->getString();
        } catch (\Throwable $th) {
            $decryptedData = "";
            $isInvalid = true;
        }

        $agent = new Agent();
        $browser = $agent->browser();
        $device = $agent->device();
        $platform = $agent->platform();
        $is_desktop = $agent->isDesktop();
        $is_expired = false;

        if (!$is_desktop && $decryptedData != "") {

            try {
                $dataObj = json_decode(($decryptedData));

                if ($this->isExpired($dataObj->creation_date, $dataObj->expires_in)) {
                    $is_expired = true;
                }

                $sessionPayload = [
                    "user_id" => $dataObj->user_id,
                    "client_user_unique_identifier" => $dataObj->client_user_unique_identifier,
                    "long" => $dataObj->long,
                    "lat" => $dataObj->lat,
                    "browser" => $browser,
                    "platform" => $platform,
                    "device" => $device,
                ];

                session(["login-payload" => json_encode($sessionPayload)]);
            } catch (\Throwable $err) {
                $isInvalid = true;
            }
        }

        $data = [
            "browser" => $browser,
            "platform" => $platform,
            "device" => $device,
            "is_desktop" => $is_desktop,
            "is_Invalid" => $isInvalid,
            "is_expired" => $is_expired
        ];

        return view("login_user_validate")->with($data);
    }

    public function login_retrieve_pin(Request $req)
    {
        $req->validate([
            "visitor_id" => "string|required",
            "long" => "string|required",
            "lat" => "string|required",
        ]);
        if (!$req->ajax()) return response()->json("Invalid request");

        try {
            $sessionPayload = json_decode(session("login-payload"));

            $getClientUserAuth = Client_user_auth::where(["user_id" => $sessionPayload->user_id, "client_user_unique_identifier" => $sessionPayload->client_user_unique_identifier]);
            if ($getClientUserAuth->first() == null) {
                return response()->json("User is not registered", 400);
            }

            $encryptionKey = KeyFactory::loadEncryptionKey('../enckey.key');
            $decryptedData = Symmetric::decrypt($getClientUserAuth->first()->auth_key, $encryptionKey)->getString();
            $authData = json_decode($decryptedData);

            $distanceInMeters = $this->distanceBetweenPoints($sessionPayload->lat, $sessionPayload->long, $req->lat, $req->long);

            if ($distanceInMeters > 450000) {
                return response()->json("Invalid location. Distance: ".$distanceInMeters." meters away", 400);
            }

            if (
                $authData->user_id == $sessionPayload->user_id &&
                $authData->client_user_unique_identifier == $sessionPayload->client_user_unique_identifier &&
                $authData->browser == $sessionPayload->browser &&
                $authData->platform == $sessionPayload->platform &&
                $authData->device == $sessionPayload->device &&
                $authData->visitor_id == $req->visitor_id
            ) {
                $pin = $this->generateRandomPIN(6);

                $getClientUserAuth->update([
                    "pin" => $pin
                ]);

                return response()->json(["pin" => $pin]);
            } else {
                return response()->json("Invalid device", 400);
            }
        } catch (\Throwable $th) {
            return response()->json("Invalid request", 400);
        }
    }

    private function isExpired($timestamp, $minutes = 5)
    {
        $carbon = Carbon::parse($timestamp);
        return $carbon->diffInMinutes(now()) > $minutes ? true : false;
    }

    private function generateRandomPIN(int $length): string
    {
        $characters = "123456789";
        $randomString = "";
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    private function distanceBetweenPoints($lat1, $lon1, $lat2, $lon2, $earthRadius = 6371000)
    {
        // convert degrees to radians
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $latDelta = $lat2 - $lat1;
        $lonDelta = $lon2 - $lon1;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}
