import unittest
from recommendation import (
    haversine,
    _resolve_region,
    _resolve_origin_city,
    build_ferry_route,
    build_bus_route,
    build_flight_route,
    apply_topsis
)

class TestRecommendation(unittest.TestCase):

    def test_haversine(self):
        # KL to JB approx 290-300km
        dist = haversine(3.1390, 101.6869, 1.4927, 103.7414)
        self.assertTrue(290 < dist < 320, f"Distance {dist} is out of expected bounds")

    def test_resolve_region(self):
        self.assertEqual(_resolve_region("Tioman Island"), "tioman")
        self.assertEqual(_resolve_region("Kuala Perlis Jetty"), "kuala perlis")
        self.assertIsNone(_resolve_region("Unknown Location"))

    def test_resolve_origin_city(self):
        self.assertEqual(_resolve_origin_city("Kuala Lumpur", 0, 0), "kuala lumpur")
        self.assertEqual(_resolve_origin_city("Johor Bahru Central", 0, 0), "johor bahru")
        # Fallback to KL if unknown name and no coords
        self.assertEqual(_resolve_origin_city("Unknown", 0, 0), "kuala lumpur")

    def test_build_ferry_route(self):
        ferry_data = {
            "origin_port_name": "Mersing Jetty",
            "origin_port_location": "Mersing, Johor",
            "origin_port_lat": 2.4333,
            "origin_port_lng": 103.8405,
            "price": 35.0,
            "duration_minutes": 90,
            "ferry_name": "Tioman Fast Ferry"
        }
        
        # Test KL to Tioman via Mersing
        route = build_ferry_route(
            origin_city="kuala lumpur",
            origin_lat=3.1390,
            origin_lng=101.6869,
            dest_port_name="Tioman",
            dest_port_location="Tioman Island",
            ferry_data=ferry_data,
            cancel_prob=0.05
        )
        
        self.assertEqual(route["mode"], "ferry")
        # Should have Grab -> Bus -> Ferry legs based on REFERENCE_ROUTES
        self.assertEqual(len(route["legs"]), 3)
        self.assertEqual(route["legs"][0]["mode"], "grab")
        self.assertEqual(route["legs"][1]["mode"], "bus")
        self.assertEqual(route["legs"][2]["mode"], "ferry")
        self.assertTrue(route["total_cost"] > 0)
        self.assertTrue(route["total_duration_min"] > 0)

    def test_build_bus_route(self):
        # Test KL to Penang (reachable by bus)
        route = build_bus_route(
            origin_city="kuala lumpur",
            origin_lat=3.1390,
            origin_lng=101.6869,
            dest_port_name="Georgetown",
            dest_port_location="Penang Island",
            dest_port_lat=5.4164,
            dest_port_lng=100.3327
        )
        self.assertIsNotNone(route)
        self.assertEqual(route["mode"], "bus")
        self.assertTrue(len(route["legs"]) >= 2) # Grab to terminal + Bus

        # Test KL to Tioman (island, not reachable by bus only)
        route_island = build_bus_route(
            origin_city="kuala lumpur",
            origin_lat=3.1390,
            origin_lng=101.6869,
            dest_port_name="Tioman",
            dest_port_location="Tioman Island",
            dest_port_lat=2.8182,
            dest_port_lng=104.1601
        )
        self.assertIsNone(route_island)

    def test_build_flight_route(self):
        # Test KL to Langkawi (has flights)
        route = build_flight_route(
            origin_city="kuala lumpur",
            dest_port_name="Kuah Jetty",
            dest_port_location="Langkawi",
            dest_port_lat=6.3265,
            dest_port_lng=99.8432
        )
        self.assertIsNotNone(route)
        self.assertEqual(route["mode"], "flight")
        self.assertEqual(len(route["legs"]), 2) # Grab to airport, Flight to Langkawi

    def test_apply_topsis(self):
        routes = [
            {"mode": "bus", "total_cost": 50, "total_duration_min": 300, "safety_risk": 0.05, "transfers": 1},
            {"mode": "flight", "total_cost": 250, "total_duration_min": 120, "safety_risk": 0.02, "transfers": 1},
            {"mode": "ferry", "total_cost": 100, "total_duration_min": 240, "safety_risk": 0.1, "transfers": 2}
        ]
        
        # Test cheapest preference
        apply_topsis(routes, preference="cheapest")
        routes.sort(key=lambda r: r["score"])
        # Bus should be first
        self.assertEqual(routes[0]["mode"], "bus")

        # Test fastest preference
        apply_topsis(routes, preference="fastest")
        routes.sort(key=lambda r: r["score"])
        # Flight should be first
        self.assertEqual(routes[0]["mode"], "flight")

if __name__ == '__main__':
    unittest.main()
