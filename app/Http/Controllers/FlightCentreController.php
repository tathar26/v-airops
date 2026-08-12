<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FlightCentreController extends Controller
{
    public function index()
    {
        return view('flight-centre.index');
    }

    public function bookFlightMap()
    {
        return view('flight-centre.book-map');
    }

    public function flightsTable()
    {
        return view('flight-centre.flights-table');
    }

    public function destinationMap()
    {
        return view('flight-centre.destination-map');
    }
}
