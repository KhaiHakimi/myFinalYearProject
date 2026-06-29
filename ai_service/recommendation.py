"""
FerryCast Multi-Modal Travel Recommendation Engine
====================================================
Compares ferry, bus, and flight options for travelling between
locations in Peninsular Malaysia. Each option is scored on price,
duration, safety, and convenience to produce a ranked recommendation.

The engine works by:
  1. Receiving origin city/coords + destination port/island
  2. Building multi-leg journeys for each transport mode
  3. Scoring and ranking them with a weighted composite formula
  4. Returning the ranked options with labels like "Best Value"
"""

import math

# ---------------------------------------------------------------------------
# Constants
# ---------------------------------------------------------------------------

# Per-km cost estimates (RM) for ground transport in Malaysia
GRAB_RATE_PER_KM = 1.30       # Grab/taxi average
GRAB_BASE_FARE = 5.00
BUS_RATE_PER_KM = 0.09        # Express bus average (~RM 30-45 for 350km)
BUS_BASE_FARE = 5.00
FLIGHT_BASE_FARE = 65.00      # Minimum domestic fare
FLIGHT_RATE_PER_KM = 0.35

# Speed assumptions for duration estimation (km/h)
GRAB_SPEED = 50
BUS_SPEED = 65
FLIGHT_SPEED = 450
FERRY_SPEED = 30              # Fallback if not in schedule

# Transfer/wait times (minutes)
BUS_TERMINAL_WAIT = 30
AIRPORT_CHECKIN = 90
FERRY_CHECKIN = 30
GRAB_WAIT = 10

# ---------------------------------------------------------------------------
# Pre-researched reference data for Malaysian transport
# ---------------------------------------------------------------------------
# Keys: (origin_region, destination_region) -> {mode, price_min, price_max, duration_min}
# This covers the major corridors. For unlisted pairs we fall back to distance estimation.

REFERENCE_ROUTES = {
    # KL area to jetty towns
    ("kuala lumpur", "mersing"): {"bus_min": 28, "bus_max": 42, "bus_dur": 270, "bus_terminal": "TBS"},
    ("kuala lumpur", "kuala besut"): {"bus_min": 40, "bus_max": 55, "bus_dur": 420, "bus_terminal": "TBS"},
    ("kuala lumpur", "kuala perlis"): {"bus_min": 45, "bus_max": 60, "bus_dur": 360, "bus_terminal": "TBS"},
    ("kuala lumpur", "kuala kedah"): {"bus_min": 38, "bus_max": 50, "bus_dur": 330, "bus_terminal": "TBS"},
    ("kuala lumpur", "lumut"): {"bus_min": 22, "bus_max": 35, "bus_dur": 240, "bus_terminal": "TBS"},
    ("kuala lumpur", "butterworth"): {"bus_min": 32, "bus_max": 45, "bus_dur": 300, "bus_terminal": "TBS"},
    ("kuala lumpur", "port klang"): {"bus_min": 3, "bus_max": 6, "bus_dur": 60, "bus_terminal": "KL Sentral"},
    ("kuala lumpur", "marang"): {"bus_min": 38, "bus_max": 50, "bus_dur": 360, "bus_terminal": "TBS"},
    ("kuala lumpur", "merang"): {"bus_min": 42, "bus_max": 55, "bus_dur": 390, "bus_terminal": "TBS"},
    ("kuala lumpur", "kuala terengganu"): {"bus_min": 38, "bus_max": 50, "bus_dur": 360, "bus_terminal": "TBS"},
    ("kuala lumpur", "tanjung gemok"): {"bus_min": 30, "bus_max": 45, "bus_dur": 300, "bus_terminal": "TBS"},
    ("kuala lumpur", "melaka"): {"bus_min": 12, "bus_max": 18, "bus_dur": 120, "bus_terminal": "TBS"},
    ("kuala lumpur", "muar"): {"bus_min": 18, "bus_max": 28, "bus_dur": 180, "bus_terminal": "TBS"},
    ("kuala lumpur", "johor bahru"): {"bus_min": 35, "bus_max": 50, "bus_dur": 270, "bus_terminal": "TBS"},

    # JB area
    ("johor bahru", "mersing"): {"bus_min": 18, "bus_max": 28, "bus_dur": 180, "bus_terminal": "Larkin"},

    # Penang area
    ("penang", "kuala perlis"): {"bus_min": 15, "bus_max": 25, "bus_dur": 120, "bus_terminal": "Sungai Nibong"},
    ("penang", "kuala kedah"): {"bus_min": 12, "bus_max": 20, "bus_dur": 90, "bus_terminal": "Sungai Nibong"},

    # Ipoh area
    ("ipoh", "lumut"): {"bus_min": 8, "bus_max": 15, "bus_dur": 75, "bus_terminal": "Amanjaya"},
}

# Flight routes (domestic Malaysia, one-way prices in RM)
FLIGHT_ROUTES = {
    ("kuala lumpur", "langkawi"): {"min": 80, "max": 350, "dur": 60, "airport": "KLIA/KLIA2", "dest_airport": "Langkawi Intl"},
    ("kuala lumpur", "terengganu"): {"min": 100, "max": 300, "dur": 60, "airport": "KLIA/KLIA2", "dest_airport": "Sultan Mahmud"},
    ("kuala lumpur", "kota bharu"): {"min": 90, "max": 280, "dur": 55, "airport": "KLIA/KLIA2", "dest_airport": "Sultan Ismail Petra"},
    ("kuala lumpur", "penang"): {"min": 70, "max": 250, "dur": 55, "airport": "KLIA/KLIA2", "dest_airport": "Penang Intl"},
    ("kuala lumpur", "johor bahru"): {"min": 80, "max": 220, "dur": 50, "airport": "KLIA/KLIA2", "dest_airport": "Senai Intl"},
    ("johor bahru", "langkawi"): {"min": 120, "max": 380, "dur": 75, "airport": "Senai Intl", "dest_airport": "Langkawi Intl"},
    ("johor bahru", "penang"): {"min": 100, "max": 300, "dur": 70, "airport": "Senai Intl", "dest_airport": "Penang Intl"},
    ("penang", "langkawi"): {"min": 70, "max": 200, "dur": 35, "airport": "Penang Intl", "dest_airport": "Langkawi Intl"},
}

# Mapping: port location keywords -> nearest city/region for lookup
PORT_REGION_MAP = {
    "kuala perlis": "kuala perlis",
    "perlis": "kuala perlis",
    "kuala kedah": "kuala kedah",
    "kedah": "kuala kedah",
    "langkawi": "langkawi",
    "kuah": "langkawi",
    "butterworth": "butterworth",
    "penang": "penang",
    "georgetown": "penang",
    "lumut": "lumut",
    "perak": "lumut",
    "pangkor": "pangkor",
    "port klang": "port klang",
    "selangor": "port klang",
    "pulau ketam": "pulau ketam",
    "mersing": "mersing",
    "johor": "johor bahru",
    "tanjung gemok": "tanjung gemok",
    "rompin": "tanjung gemok",
    "pahang": "tanjung gemok",
    "tioman": "tioman",
    "kuala terengganu": "kuala terengganu",
    "terengganu": "kuala terengganu",
    "merang": "merang",
    "setiu": "merang",
    "kuala besut": "kuala besut",
    "besut": "kuala besut",
    "redang": "redang",
    "perhentian": "perhentian",
    "marang": "marang",
    "kapas": "kapas",
    "melaka": "melaka",
    "muar": "muar",
    "iskandar puteri": "johor bahru",
    "dumai": "dumai",
    "bengkalis": "bengkalis",
    "batam": "batam",
}

# Destinations reachable by flight (maps island/destination to flight key)
FLIGHT_DESTINATION_MAP = {
    "langkawi": "langkawi",
    "kuala terengganu": "terengganu",
    "merang": "terengganu",
    "kuala besut": "kota bharu",
    "redang": "terengganu",
    "perhentian": "kota bharu",
    "penang": "penang",
    "butterworth": "penang",
    "johor bahru": "johor bahru",
    "iskandar puteri": "johor bahru",
}

# City coordinates for distance calculations
CITY_COORDS = {
    "kuala lumpur": (3.1390, 101.6869),
    "johor bahru": (1.4927, 103.7414),
    "penang": (5.4164, 100.3327),
    "ipoh": (4.5975, 101.0901),
    "melaka": (2.1896, 102.2501),
    "kuantan": (3.8077, 103.3260),
    "kota bharu": (6.1254, 102.2381),
    "kuala terengganu": (5.3117, 103.1324),
    "shah alam": (3.0738, 101.5183),
    "petaling jaya": (3.1073, 101.6067),
    "subang jaya": (3.0565, 101.5851),
    "putrajaya": (2.9264, 101.6964),
    "cyberjaya": (2.9188, 101.6538),
    "seremban": (2.7258, 101.9424),
}


def haversine(lat1, lon1, lat2, lon2):
    """Calculate distance in km between two coordinates."""
    R = 6371
    dlat = math.radians(lat2 - lat1)
    dlon = math.radians(lon2 - lon1)
    a = (math.sin(dlat / 2) ** 2 +
         math.cos(math.radians(lat1)) * math.cos(math.radians(lat2)) *
         math.sin(dlon / 2) ** 2)
    return R * 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a))


def _resolve_region(location_str, port_name=None):
    """Map a port location/name string to a known region key."""
    # Check port name first (more specific, e.g. 'Tioman Island Marina')
    for source in [port_name, location_str]:
        if not source:
            continue
        loc = source.lower().strip()
        for keyword, region in PORT_REGION_MAP.items():
            if keyword in loc:
                return region
    return None


def _resolve_origin_city(origin_name, origin_lat, origin_lng):
    """Find the closest known city to the user's origin."""
    if origin_name:
        name_lower = origin_name.lower()
        for city in CITY_COORDS:
            if city in name_lower:
                return city

    # Fall back to nearest city by coordinates
    if origin_lat and origin_lng:
        best_city = None
        best_dist = float("inf")
        for city, (clat, clng) in CITY_COORDS.items():
            d = haversine(origin_lat, origin_lng, clat, clng)
            if d < best_dist:
                best_dist = d
                best_city = city
        if best_dist < 100:
            return best_city
    return "kuala lumpur"


def calculate_road_cost(dist_km, fixed_fare=None):
    """
    Detailed cost breakdown for road transport (Grab/Taxi).
    Returns oil, toll, and grab_fare components.
    """
    if fixed_fare is not None and dist_km == 0:
        return {
            "total": float(fixed_fare),
            "oil": 0.00,
            "toll": 0.00,
            "grab_fare": float(fixed_fare)
        }
        
    oil = round(dist_km * 0.15, 2)
    toll = round(dist_km * 0.10, 2) if dist_km > 15 else 0.00
    grab_fare = round(GRAB_BASE_FARE + (dist_km * 0.85), 2)
    total = round(oil + toll + grab_fare, 2)
    
    return {
        "total": total,
        "oil": oil,
        "toll": toll,
        "grab_fare": grab_fare
    }


def build_ferry_route(origin_city, origin_lat, origin_lng,
                      dest_port_name, dest_port_location,
                      ferry_data, cancel_prob=None):
    """
    Build a multi-leg ferry journey:
      User Location -> (grab/bus) -> Origin Jetty -> (ferry) -> Destination

    ferry_data should contain:
      - origin_port_name, origin_port_lat, origin_port_lng
      - price, duration_minutes, ferry_name, departure_time
    """
    legs = []
    total_cost = 0
    total_duration = 0

    o_port_lat = ferry_data.get("origin_port_lat", 0)
    o_port_lng = ferry_data.get("origin_port_lng", 0)

    # Leg 1: Get to the jetty (Grab or Bus)
    dist_to_jetty = haversine(origin_lat, origin_lng, o_port_lat, o_port_lng)
    jetty_region = _resolve_region(ferry_data.get("origin_port_location", ""), ferry_data.get("origin_port_name", ""))
    bus_ref = REFERENCE_ROUTES.get((origin_city, jetty_region))

    if dist_to_jetty < 5:
        # Close enough to walk/short grab
        rc = calculate_road_cost(dist_to_jetty)
        grab_dur = max(10, dist_to_jetty / GRAB_SPEED * 60)
        legs.append({
            "mode": "grab",
            "from": "Your Location",
            "to": ferry_data.get("origin_port_name", "Jetty"),
            "distance_km": round(dist_to_jetty, 1),
            "cost": rc["total"],
            "cost_breakdown": rc,
            "duration_min": round(grab_dur),
            "note": "Short ride to jetty"
        })
        total_cost += rc["total"]
        total_duration += grab_dur + GRAB_WAIT
    elif bus_ref:
        # Use researched bus price
        bus_cost = (bus_ref["bus_min"] + bus_ref["bus_max"]) / 2
        legs.append({
            "mode": "bus",
            "from": bus_ref.get("bus_terminal", "Bus Terminal"),
            "to": ferry_data.get("origin_port_name", "Jetty"),
            "distance_km": round(dist_to_jetty, 1),
            "cost": round(bus_cost, 2),
            "duration_min": bus_ref["bus_dur"],
            "note": f"Express bus (RM {bus_ref['bus_min']}-{bus_ref['bus_max']})"
        })
        # Add grab to bus terminal
        rc = calculate_road_cost(0, fixed_fare=8.00)
        legs.insert(0, {
            "mode": "grab",
            "from": "Your Location",
            "to": bus_ref.get("bus_terminal", "Bus Terminal"),
            "distance_km": 0,
            "cost": rc["total"],
            "cost_breakdown": rc,
            "duration_min": 15,
            "note": "Grab to bus terminal"
        })
        total_cost += bus_cost + rc["total"]
        total_duration += bus_ref["bus_dur"] + BUS_TERMINAL_WAIT + 15
    else:
        # Distance-based estimation
        if dist_to_jetty > 30:
            bus_cost = BUS_BASE_FARE + dist_to_jetty * BUS_RATE_PER_KM
            bus_dur = dist_to_jetty / BUS_SPEED * 60
            legs.append({
                "mode": "bus",
                "from": "Nearest Bus Terminal",
                "to": ferry_data.get("origin_port_name", "Jetty"),
                "distance_km": round(dist_to_jetty, 1),
                "cost": round(bus_cost, 2),
                "duration_min": round(bus_dur),
                "note": "Estimated bus fare"
            })
            total_cost += bus_cost
            total_duration += bus_dur + BUS_TERMINAL_WAIT
        else:
            rc = calculate_road_cost(dist_to_jetty)
            grab_dur = dist_to_jetty / GRAB_SPEED * 60
            legs.append({
                "mode": "grab",
                "from": "Your Location",
                "to": ferry_data.get("origin_port_name", "Jetty"),
                "distance_km": round(dist_to_jetty, 1),
                "cost": rc["total"],
                "cost_breakdown": rc,
                "duration_min": round(grab_dur),
                "note": "Grab ride"
            })
            total_cost += rc["total"]
            total_duration += grab_dur + GRAB_WAIT

    # Leg 2: Ferry crossing
    ferry_price = float(ferry_data.get("price", 35))
    ferry_dur = int(ferry_data.get("duration_minutes", 90))
    legs.append({
        "mode": "ferry",
        "from": ferry_data.get("origin_port_name", "Origin Jetty"),
        "to": dest_port_name or "Destination",
        "distance_km": 0,
        "cost": round(ferry_price, 2),
        "duration_min": ferry_dur,
        "ferry_name": ferry_data.get("ferry_name", ""),
        "departure_time": ferry_data.get("departure_time", ""),
        "note": f"Ferry: {ferry_data.get('ferry_name', 'N/A')}"
    })
    total_cost += ferry_price
    total_duration += ferry_dur + FERRY_CHECKIN

    safety = cancel_prob if cancel_prob is not None else 0.1
    transfers = len(legs) - 1

    return {
        "mode": "ferry",
        "label": "🚢 Ferry Route",
        "legs": legs,
        "total_cost": round(total_cost, 2),
        "total_duration_min": round(total_duration),
        "safety_risk": round(safety, 4),
        "transfers": transfers,
        "currency": "MYR",
    }


def build_bus_route(origin_city, origin_lat, origin_lng,
                    dest_port_name, dest_port_location,
                    dest_port_lat, dest_port_lng):
    """
    Build a bus-only route. Only viable for mainland destinations,
    not for islands (Tioman, Langkawi, Perhentian, etc.)
    """
    dest_region = _resolve_region(dest_port_location, dest_port_name)
    if not dest_region:
        return None

    # Islands cannot be reached by bus alone
    islands = ["langkawi", "tioman", "pangkor", "redang", "perhentian",
               "kapas", "pulau ketam", "batam", "dumai", "bengkalis"]
    if dest_region in islands:
        return None

    bus_ref = REFERENCE_ROUTES.get((origin_city, dest_region))
    dist = haversine(origin_lat, origin_lng, dest_port_lat, dest_port_lng)

    legs = []
    total_cost = 0
    total_duration = 0

    # Grab to bus terminal
    rc = calculate_road_cost(0, fixed_fare=8.00)
    legs.append({
        "mode": "grab",
        "from": "Your Location",
        "to": bus_ref["bus_terminal"] if bus_ref else "Bus Terminal",
        "distance_km": 0,
        "cost": rc["total"],
        "cost_breakdown": rc,
        "duration_min": 15,
        "note": "Grab to bus terminal"
    })
    total_cost += rc["total"]
    total_duration += 15 + BUS_TERMINAL_WAIT

    if bus_ref:
        bus_cost = (bus_ref["bus_min"] + bus_ref["bus_max"]) / 2
        legs.append({
            "mode": "bus",
            "from": bus_ref.get("bus_terminal", "Bus Terminal"),
            "to": dest_port_name,
            "distance_km": round(dist, 1),
            "cost": round(bus_cost, 2),
            "duration_min": bus_ref["bus_dur"],
            "note": f"Express bus (RM {bus_ref['bus_min']}-{bus_ref['bus_max']})"
        })
        total_cost += bus_cost
        total_duration += bus_ref["bus_dur"]
    else:
        bus_cost = BUS_BASE_FARE + dist * BUS_RATE_PER_KM
        bus_dur = dist / BUS_SPEED * 60
        legs.append({
            "mode": "bus",
            "from": "Bus Terminal",
            "to": dest_port_name,
            "distance_km": round(dist, 1),
            "cost": round(bus_cost, 2),
            "duration_min": round(bus_dur),
            "note": "Estimated bus fare"
        })
        total_cost += bus_cost
        total_duration += bus_dur

    return {
        "mode": "bus",
        "label": "🚌 Bus Route",
        "legs": legs,
        "total_cost": round(total_cost, 2),
        "total_duration_min": round(total_duration),
        "safety_risk": 0.05,
        "transfers": len(legs) - 1,
        "currency": "MYR",
    }


def build_flight_route(origin_city, dest_port_name, dest_port_location, dest_port_lat, dest_port_lng):
    """
    Build a flight route if one exists for this origin-destination pair.
    """
    dest_region = _resolve_region(dest_port_location, dest_port_name)
    if not dest_region:
        return None

    flight_dest = FLIGHT_DESTINATION_MAP.get(dest_region)
    if not flight_dest:
        return None

    flight_key = (origin_city, flight_dest)
    flight_ref = FLIGHT_ROUTES.get(flight_key)
    if not flight_ref:
        return None

    legs = []
    total_cost = 0
    total_duration = 0

    # Grab to airport
    airport_name = flight_ref["airport"]
    fixed_fare = 75.00 if "KLIA" in airport_name else 25.00
    rc = calculate_road_cost(0, fixed_fare=fixed_fare)
    legs.append({
        "mode": "grab",
        "from": "Your Location",
        "to": airport_name,
        "distance_km": 0,
        "cost": rc["total"],
        "cost_breakdown": rc,
        "duration_min": 45 if "KLIA" in airport_name else 20,
        "note": f"Grab to {airport_name}"
    })
    total_cost += rc["total"]
    total_duration += (45 if "KLIA" in airport_name else 20) + AIRPORT_CHECKIN

    # Flight
    flight_cost = (flight_ref["min"] + flight_ref["max"]) / 2
    legs.append({
        "mode": "flight",
        "from": airport_name,
        "to": flight_ref["dest_airport"],
        "distance_km": 0,
        "cost": round(flight_cost, 2),
        "duration_min": flight_ref["dur"],
        "note": f"Flight (RM {flight_ref['min']}-{flight_ref['max']})"
    })
    total_cost += flight_cost
    total_duration += flight_ref["dur"]

    islands_needing_extra = ["redang", "perhentian", "kapas"]
    if dest_region in islands_needing_extra:
        rc = calculate_road_cost(0, fixed_fare=25.00)
        legs.append({
            "mode": "grab",
            "from": flight_ref["dest_airport"],
            "to": "Jetty (for boat transfer)",
            "distance_km": 0,
            "cost": rc["total"],
            "cost_breakdown": rc,
            "duration_min": 30,
            "note": "Taxi to nearest jetty + boat"
        })
        total_cost += rc["total"]
        total_duration += 30

    return {
        "mode": "flight",
        "label": "✈️ Flight Route",
        "legs": legs,
        "total_cost": round(total_cost, 2),
        "total_duration_min": round(total_duration),
        "safety_risk": 0.02,
        "transfers": len(legs) - 1,
        "currency": "MYR",
    }


def apply_topsis(all_routes, preference="balanced"):
    """
    Score and rank routes using the TOPSIS Algorithm
    (Technique for Order of Preference by Similarity to Ideal Solution).
    
    TOPSIS evaluates alternatives based on multiple criteria by measuring
    the distance to the ideal best and ideal worst solutions.
    """
    if not all_routes:
        return
        
    weights = {
        "balanced": [0.35, 0.25, 0.25, 0.15],
        "cheapest": [0.94, 0.02, 0.02, 0.02],
        "fastest":  [0.02, 0.94, 0.02, 0.02],
        "safest":   [0.02, 0.02, 0.94, 0.02],
    }
    w = weights.get(preference, weights["balanced"])
    
    # Step 1: Create Decision Matrix
    # Criteria: [Cost, Duration, Safety, Transfers] (All are Cost criteria i.e. lower is better)
    matrix = []
    for r in all_routes:
        matrix.append([
            float(r["total_cost"]),
            float(r["total_duration_min"]),
            float(r.get("safety_risk", 0.05)),
            float(r.get("transfers", 0))
        ])
        
    # Step 2: Normalize the Decision Matrix
    n_routes = len(matrix)
    n_criteria = 4
    norm_matrix = [[0]*n_criteria for _ in range(n_routes)]
    
    for j in range(n_criteria):
        sq_sum = sum(matrix[i][j]**2 for i in range(n_routes))
        denom = math.sqrt(sq_sum) if sq_sum > 0 else 1
        for i in range(n_routes):
            norm_matrix[i][j] = matrix[i][j] / denom
            
    # Step 3: Calculate Weighted Normalized Matrix
    weighted_matrix = [[0]*n_criteria for _ in range(n_routes)]
    for i in range(n_routes):
        for j in range(n_criteria):
            weighted_matrix[i][j] = norm_matrix[i][j] * w[j]
            
    # Step 4: Determine Ideal Best (V+) and Ideal Worst (V-)
    # Since all criteria are cost-based (lower is better):
    # Ideal Best = Minimum in column, Ideal Worst = Maximum in column
    v_plus = []
    v_minus = []
    for j in range(n_criteria):
        col_vals = [weighted_matrix[i][j] for i in range(n_routes)]
        v_plus.append(min(col_vals))
        v_minus.append(max(col_vals))
        
    # Step 5 & 6: Calculate Euclidean Distances and Performance Score
    for i, r in enumerate(all_routes):
        d_plus = math.sqrt(sum((weighted_matrix[i][j] - v_plus[j])**2 for j in range(n_criteria)))
        d_minus = math.sqrt(sum((weighted_matrix[i][j] - v_minus[j])**2 for j in range(n_criteria)))
        
        # Performance Score: closer to 1 is better
        # We invert it (1 - score) for our frontend which expects lower score = better rank, 
        # or we just use it directly and sort descending.
        # Let's use (1 - performance_score) so lower is better, matching previous logic.
        if d_plus + d_minus == 0:
            perf_score = 0
        else:
            perf_score = d_minus / (d_plus + d_minus)
            
        # Lower score is better for sorting
        r["score"] = round(1.0 - perf_score, 4)



def recommend(payload, model=None, predict_fn=None):
    """
    Main recommendation entry point.

    payload keys:
      - origin_name: str (city name or location description)
      - origin_lat, origin_lng: float
      - dest_port_name: str
      - dest_port_location: str
      - dest_port_lat, dest_port_lng: float
      - ferry_options: list of dicts with ferry schedule data
      - preference: str ("balanced", "cheapest", "fastest", "safest")

    model + predict_fn: optional, for ferry safety prediction
    """
    origin_name = payload.get("origin_name", "")
    origin_lat = float(payload.get("origin_lat", 3.139))
    origin_lng = float(payload.get("origin_lng", 101.687))
    dest_port_name = payload.get("dest_port_name", "")
    dest_port_location = payload.get("dest_port_location", "")
    dest_port_lat = float(payload.get("dest_port_lat", 0))
    dest_port_lng = float(payload.get("dest_port_lng", 0))
    ferry_options = payload.get("ferry_options", [])
    preference = payload.get("preference", "balanced")

    origin_city = _resolve_origin_city(origin_name, origin_lat, origin_lng)

    routes = []

    # --- Build Ferry Routes (one per schedule option, pick best) ---
    best_ferry = None
    for fo in ferry_options:
        cancel_prob = None
        if model and predict_fn:
            try:
                from model import FEATURE_NAMES
                features = {f: float(fo.get(f, 0)) for f in FEATURE_NAMES}
                pred = predict_fn(model, features)
                cancel_prob = pred.get("cancellation_probability", 0.1)
            except Exception:
                cancel_prob = 0.1

        ferry_route = build_ferry_route(
            origin_city, origin_lat, origin_lng,
            dest_port_name, dest_port_location,
            fo, cancel_prob
        )
        if not isinstance(best_ferry, dict) or ferry_route["total_cost"] < best_ferry.get("total_cost", float('inf')):
            best_ferry = ferry_route

    if best_ferry:
        routes.append(best_ferry)

    # --- Build Bus Route ---
    bus_route = build_bus_route(
        origin_city, origin_lat, origin_lng,
        dest_port_name, dest_port_location,
        dest_port_lat, dest_port_lng
    )
    if bus_route:
        routes.append(bus_route)

    # --- Build Flight Route ---
    flight_route = build_flight_route(
        origin_city, dest_port_name, dest_port_location,
        dest_port_lat, dest_port_lng
    )
    if flight_route:
        routes.append(flight_route)

    # --- Score and Rank using TOPSIS ---
    apply_topsis(routes, preference)

    routes.sort(key=lambda r: r["score"])

    # --- Assign Labels ---
    if routes:
        routes[0]["tags"] = ["AI Recommended"]

        # Find specific bests
        cheapest = min(routes, key=lambda r: r["total_cost"])
        fastest = min(routes, key=lambda r: r["total_duration_min"])
        safest = min(routes, key=lambda r: r["safety_risk"])

        for r in routes:
            if "tags" not in r:
                r["tags"] = []
            if r is cheapest:
                r["tags"].append("Best Value")
            if r is fastest:
                r["tags"].append("Fastest")
            if r is safest:
                r["tags"].append("Safest")

    return {
        "origin": origin_name or origin_city.title(),
        "destination": dest_port_name,
        "preference": preference,
        "recommendations": routes,
        "total_options": len(routes),
    }
