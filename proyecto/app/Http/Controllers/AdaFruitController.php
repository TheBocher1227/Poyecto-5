<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\SensorData;
use Illuminate\Support\Facades\Log;

class AdaFruitController extends Controller
{
    public function consumirdistancia(Request $request)
    {
        
        $client = new Client();

        try {
            $response = $client->get('https://io.adafruit.com/api/v2/Teemo_abejita/groups/manhattan', [
                'headers' => [
                    'X-AIO-Key' => 'aio_khqx53iPoLwd4rAUxmahx3usnc5j',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            $this->guardarDatosEnDB($data);

            return response()->json([
                "msg"  => "Success",
                "data" => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "msg"  => "Error",
                "data" => $e->getMessage()
            ], 500); 
        }
    }

    private function guardarDatosEnDB($data)
    {
        foreach ($data['feeds'] as $feed) {
            SensorData::updateOrCreate(
                ['key' => $feed['key']], 
                [
                    'name'       => $feed['name'],
                    'last_value' => $feed['last_value'],
                ]
            );
        }
    }

   
    public function controlLuces(Request $request)
    {
       
        $data = $request->all();

        
        if (empty($data)) {
            return response()->json([
                "msg" => "Error: No se proporcionaron datos en el cuerpo de la solicitud.",
            ], 422);
        }

        
        $client = new Client();
        $response = $client->post('https://io.adafruit.com/api/v2/Teemo_abejita/groups/manhattan/feeds/fotoresistenciastatus/data', [
            'headers' => [
                'X-AIO-Key' => 'aio_khqx53iPoLwd4rAUxmahx3usnc5j',
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        
        if ($response->getStatusCode() == 200) {
            return response()->json([
                "msg" => "Datos enviados a Adafruit IO correctamente",
            ], 200);
        } else {
            return response()->json([
                "msg" => "Error al enviar datos a Adafruit IO",
            ], 500);
        }
    }
}
