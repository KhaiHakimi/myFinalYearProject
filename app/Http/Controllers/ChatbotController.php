<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * Process a user message and return a smart, conversational response
     * based on FerryCast data (schedules, weather risk, etc).
     */
    public function message(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'context_url' => 'nullable|string'
        ]);

        $message = strtolower($request->input('message'));
        $contextUrl = $request->input('context_url', '/');
        
        // 1. Parse Intent & Entities (Heuristic Natural Language Processing)
        $intent = $this->determineIntent($message);
        $entities = $this->extractEntities($message);

        // 2. Map Entities to Database Objects
        $originPort = $this->findPort($entities['origin']);
        $destinationPort = $this->findPort($entities['destination']);
        $date = $this->parseDate($entities['date_str']);

        // Check if user is asking about the current page
        if (str_contains($message, 'what page am i on') || str_contains($message, 'where am i')) {
            if (str_contains($contextUrl, '/schedules')) {
                return response()->json(['reply' => "You are currently on the **Schedules** page. Here you can browse all upcoming ferry departures, see live weather, and book tickets."]);
            } elseif (str_contains($contextUrl, '/our-fleet')) {
                return response()->json(['reply' => "You're viewing the **Our Fleet** page! This section tells you all about the vessels participating in the FerryCast network. Ask me if you want to know about a specific ferry's seating capacity or rating."]);
            } elseif (str_contains($contextUrl, '/dashboard')) {
                return response()->json(['reply' => "Welcome to your **Dashboard**. It's a quick overview of your account."]);
            } else {
                return response()->json(['reply' => "You are at **" . ($contextUrl !== '/' ? $contextUrl : 'the Homepage') . "**. I travel with you across the whole site, so you can ask me for a ferry schedule anytime!"]);
            }
        }

        // 3. Generate Response
        if ($intent === 'greeting') {
            return response()->json([
                'reply' => "Hello! 👋 I'm your FerryCast Smart Assistant. I can help you find the safest and best ferry travel windows based on live marine weather forecasts. Just tell me where you want to go, for example: *\"Kedah to Langkawi tomorrow\"*."
            ]);
        }

        if ($intent === 'identity') {
            return response()->json([
                'reply' => "I am the **FerryCast Smart Assistant**, an AI-powered travel agent built to help you find the safest marine travel windows! I use live meteorological data from Open-Meteo to analyze wave heights and wind speeds, ensuring you get to your destination safely! 🚢"
            ]);
        }

        if ($intent === 'refund_inquiry') {
            return response()->json([
                'reply' => "⚠️ **Modifications & Cancellations:**\nIf you need to change your booking date or cancel your ticket, you must do so at least **24 hours** before departure. You can manage this from your **[My Bookings](/bookings)** page or by contacting our terminal agents."
            ]);
        }

        if ($intent === 'price_inquiry') {
            return response()->json([
                'reply' => "Ferry ticket prices vary by route and operator! For example, Penang to Butterworth is very cheap (around RM 2.00), while a trip to Tioman or Langkawi can range from RM 18.00 to RM 35.00+. Children and seniors usually get discounts. \n\nTell me where you want to go and I'll find exact ticket prices for you!"
            ]);
        }

        if ($intent === 'weather_inquiry') {
            return response()->json([
                'reply' => "FerryCast utilizes **live marine data from Open-Meteo** to track real-time wave heights, wind gusts, and precipitation across all major Malaysian routes! \n\nWe pass this data into an **AI Risk Engine** to determine if a departure is safe. If you tell me your route (e.g. \"*Penang tomorrow*\"), I can tell you the exact predicted wave height!"
            ]);
        }
        
        if ($intent === 'ferry_info') {
            return response()->json([
                'reply' => "🛳️ **Ferry Facilities & Rules:**\n- **Vehicles (RoRo)**: Currently, only specific ferries out of Kuala Perlis and Labuan allow you to bring your car. Look for the 'RoRo' ticket type when booking.\n- **Luggage**: Standard allowance is usually 15kg to 20kg per passenger.\n- **Pets**: Most passenger ferries do not allow pets.\n\nYou can visit our **[Our Fleet](/our-fleet)** page to see exactly what amenities (like Air Conditioning, Food, or VIP seating) are available on specific vessels!"
            ]);
        }

        if ($intent === 'contact') {
            return response()->json([
                'reply' => "📞 **FerryCast Support**\nIf you need help with a booking, refunds, or technical issues, you can reach out to our team at:\n- **Email**: support@ferrycast.com\n- **Phone**: +60 12-345-6789 (9 AM - 6 PM MYT)\n\nWe're always here to ensure you have a smooth voyage!"
            ]);
        }

        if ($intent === 'help' || (!$originPort && !$destinationPort && $intent === 'search_schedule')) {
            return response()->json([
                'reply' => "I can analyze weather risks and find the best ferries for you! Try asking:\n- *\"What's the safest time to go from Penang to Langkawi tomorrow?\"*\n- *\"How much is a ticket to Tioman?\"*\n- *\"Can I bring my car on the ferry?\"*"
            ]);
        }

        // We have at least one port. Let's see what we can do.
        if ($originPort && !$destinationPort) {
            $destinations = Schedule::where('origin_port_id', $originPort->id)
                ->where('departure_time', '>=', now())
                ->with('destination')
                ->get()
                ->pluck('destination.name')
                ->unique()
                ->filter()
                ->values();

            if ($destinations->isEmpty()) {
                return response()->json([
                    'reply' => "I'm sorry, I couldn't find any upcoming departures departing from **{$originPort->name}**."
                ]);
            }

            $list = $destinations->map(fn($d) => "- {$d}")->join("\n");
            return response()->json([
                'reply' => "I found several routes departing from **{$originPort->name}**. Where would you like to go?\n\n{$list}"
            ]);
        }

        if (!$originPort && $destinationPort) {
            return response()->json([
                'reply' => "I see you want to go to **{$destinationPort->name}**. Where will you be departing from?"
            ]);
        }

        // We have both an origin and destination! Let's analyze the travel window.
        return $this->analyzeRouteConversational($originPort, $destinationPort, $date);
    }

    /**
     * Determines the primary intent of the user's message.
     */
    private function determineIntent(string $message): string
    {
        if (preg_match('/\b(hi|hello|hey|greetings|start|good morning|good afternoon)\b/', $message)) return 'greeting';
        if (preg_match('/\b(who are you|who made you|what are you|what is ferrycast|about you)\b/', $message)) return 'identity';
        if (preg_match('/\b(price|cost|fare|ticket|how much|student|discount|rm)\b/', $message)) return 'price_inquiry';
        if (preg_match('/\b(weather|rain|storm|waves|wind|sea level|rough sea|forecast)\b/', $message)) return 'weather_inquiry';
        if (preg_match('/\b(car|roro|vehicle|drive|luggage|pets|dog|cat|food|amenities|eat|facilities|bring|wifi)\b/', $message)) return 'ferry_info';
        if (preg_match('/\b(cancel|refund|change ticket|modify booking|wrong date)\b/', $message)) return 'refund_inquiry';
        if (preg_match('/\b(contact|support|help desk|phone|call|email|reach us|operator|issue)\b/', $message)) return 'contact';
        if (preg_match('/\b(help|how|what can you do|features)\b/', $message)) return 'help';
        
        return 'search_schedule';
    }

    /**
     * Extracts potential origin, destination, and temporal words from the message.
     */
    private function extractEntities(string $message): array
    {
        $entities = ['origin' => null, 'destination' => null, 'date_str' => null];

        // 1. Extract Temporal Entity
        if (preg_match('/\b(tomorrow|today|this weekend|monday|tuesday|wednesday|thursday|friday|saturday|sunday|next week|[\d\-\/]+)\b/', $message, $matches)) {
            $entities['date_str'] = $matches[1];
        }

        // 2. Extract Geographic Entities via Dictionary & Positional Matching
        $ports = Port::all();
        $matchedPorts = [];

        foreach ($ports as $port) {
            $normalizedName = trim(str_replace([' jetty', ' port', ' terminal', ' (langkawi)', ' passenger', ' international', ' cruise', ' marina', ' jeti'], '', strtolower($port->name)));
            
            $pos = strpos($message, $normalizedName);
            if ($pos !== false) {
                if (!isset($matchedPorts[$pos])) {
                    $matchedPorts[$pos] = $port;
                }
            } else {
                if ($port->location) {
                    $locs = array_map('trim', explode(',', strtolower($port->location)));
                    foreach ($locs as $loc) {
                        if (strlen($loc) > 3) {
                            $p = strpos($message, $loc);
                            if ($p !== false) {
                                if (!isset($matchedPorts[$p])) {
                                    $matchedPorts[$p] = $port;
                                }
                                break; 
                            }
                        }
                    }
                }
            }
        }

        ksort($matchedPorts);

        $foundUnique = [];
        $foundNames = [];

        // Reduce duplicated regions (e.g. if 'Penang' is mentioned, grab the first seeded Penang port)
        foreach ($matchedPorts as $pos => $port) {
            $key = $port->location ?? $port->name;
            if (!in_array($key, $foundUnique)) {
                $foundUnique[] = $key;
                $foundNames[$pos] = $port;
            }
        }

        $finalPorts = array_values($foundNames);

        // 3. Assign Origin and Destination based on context / position
        if (count($finalPorts) >= 2) {
            $firstPos = array_keys($foundNames)[0];
            $beforeFirst = substr($message, 0, $firstPos);
            
            // If the user said "to X from Y" vs "from X to Y"
            if (preg_match('/to\s+$/', $beforeFirst)) {
                $entities['destination'] = $finalPorts[0]->name;
                $entities['origin'] = $finalPorts[1]->name;
            } else {
                $entities['origin'] = $finalPorts[0]->name;
                $entities['destination'] = $finalPorts[1]->name;
            }
        } elseif (count($finalPorts) == 1) {
            $firstPos = array_keys($foundNames)[0];
            $beforeFirst = substr($message, 0, $firstPos);
            
            if (preg_match('/to\s+$/', $beforeFirst)) {
                $entities['destination'] = $finalPorts[0]->name;
            } else {
                $entities['origin'] = $finalPorts[0]->name;
            }
        }

        return $entities;
    }

    private function findPort(?string $name): ?Port
    {
        if (!$name) return null;

        return Port::where('name', 'LIKE', "%{$name}%")
            ->orWhere('location', 'LIKE', "%" . str_replace(' ', '%', $name) . "%")
            ->first();
    }

    private function parseDate(?string $dateStr): Carbon
    {
        if (!$dateStr) return now('Asia/Singapore');

        $dateStr = strtolower($dateStr);
        if (str_contains($dateStr, 'tomorrow')) return now('Asia/Singapore')->addDay();
        if (str_contains($dateStr, 'weekend')) return now('Asia/Singapore')->next(Carbon::SATURDAY);
        if (str_contains($dateStr, 'next week')) return now('Asia/Singapore')->addWeek();
        
        try {
            return Carbon::parse($dateStr, 'Asia/Singapore');
        } catch (\Exception $e) {
            return now('Asia/Singapore');
        }
    }

    /**
     * Formats the Travel Window API logic into a chat reply.
     */
    private function analyzeRouteConversational(Port $origin, Port $destination, Carbon $targetDate)
    {
        // Re-use the logic from TravelWindowController
        $controller = new TravelWindowController();
        
        $request = new Request([
            'origin_port_id' => $origin->id,
            'destination_port_id' => $destination->id,
            'days' => 7
        ]);

        $response = $controller->analyze($request)->getData(true);

        if (isset($response['error']) || empty($response['windows'])) {
            return response()->json([
                'reply' => "I'm sorry, I couldn't find any scheduled departures from **{$origin->name}** to **{$destination->name}** in the next 7 days."
            ]);
        }

        // Filter windows to target date
        $targetDateString = $targetDate->format('l, j M');
        $dayWindows = collect($response['windows'])->filter(fn($w) => $w['date_label'] === $targetDateString)->values();

        if ($dayWindows->isEmpty()) {
            // Find the next available day
            $nextAvailable = $response['windows'][0];
            return response()->json([
                'reply' => "I couldn't find any ferries on **{$targetDateString}**. However, the next safest option is on **{$nextAvailable['date_label']}** at **{$nextAvailable['time_label']}** via {$nextAvailable['ferry_name']}."
            ]);
        }

        // Sort by time for the conversational list
        $sortedByTime = $dayWindows->sortBy('departure_time')->values();
        
        // Find safest on that day
        $safest = $dayWindows->sortBy('risk_score')->first();

        // Build the response
        $reply = "Here's what I found for **{$origin->name} → {$destination->name}** on **{$targetDateString}**:\n\n";
        
        $reply .= "🏆 **Safest Option:**\nThe **{$safest['time_label']}** departure via **{$safest['ferry_name']}** is the safest choice. The Open-Meteo marine forecast predicts calm waves around **{$safest['wave_height']}m** and **{$safest['wind_speed']} km/h** winds (Risk Score: {$safest['risk_score']}% - {$safest['risk_status']}).\n\n";

        $reply .= "🗓️ **All schedule times for that day:**\n";
        foreach ($sortedByTime as $w) {
            $icon = $w['risk_score'] >= 70 ? '🔴' : ($w['risk_score'] >= 30 ? '🟡' : '🟢');
            $reply .= "- **{$w['time_label']}** - {$w['ferry_name']} (RM {$w['price']}) - {$icon} {$w['risk_status']}\n";
        }

        $reply .= "\nWould you like me to take you to the booking page for the {$safest['time_label']} departure?";

        return response()->json([
            'reply' => $reply,
            'action' => 'show_booking',
            'schedule_id' => $safest['schedule_id']
        ]);
    }
}
