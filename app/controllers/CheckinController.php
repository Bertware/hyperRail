<?php

class CheckinController extends BaseController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		//TODO: remove code duplication and put this in BaseController
        $negotiator = new \Negotiation\FormatNegotiator();
        $acceptHeader = Request::header('accept');
        $priorities = array('text/html', 'application/json', '*/*');
        $result = $negotiator->getBest($acceptHeader, $priorities);
        $val = "text/html";
        //unless the negotiator has found something better for us
        if (isset($result)) {
            $val = $result->getValue();
        }

       	if (Sentry::check()) {
			$user = Sentry::getUser();
			$checkins = Checkin::where('user_id', $user->id)->get();
		} else{
			return Redirect::to('/login');
		}

        switch ($val){
            case "application/json":
            case "application/ld+json":
            	if (Sentry::check()) {
            		$data = json_encode($data);
            		return Response::make($data, 200)->header('Content-Type', 'application/ld+json')->header('Vary', 'accept')->header('Access-Control-Allow-Origin', 'https://irail.dev');
            	}
            	return Response::make("Unauthorized Access", 403);
            break;
            case "text/html":
            default:
				return View::make('checkins.index', array('checkins' => $checkins));
            break;
		}
	}

	/**
	 * Display a listing of resources of an id
	 *
	 * @return  response
	 */
	public function show($id)
	{
		//TODO: remove code duplication and put this in BaseController
        $negotiator = new \Negotiation\FormatNegotiator();
        $acceptHeader = Request::header('accept');
        $priorities = array('text/html', 'application/json', '*/*');
        $result = $negotiator->getBest($acceptHeader, $priorities);
        $val = "text/html";
        //unless the negotiator has found something better for us
        if (isset($result)) {
            $val = $result->getValue();
        }

		$checkins = Checkin::where('user_id', $id)->get()->toJson();

		switch ($val){
            case "application/json":
            case "application/ld+json":
            	return Response::make($checkins, 200)->header('Content-Type', 'application/ld+json')->header('Vary', 'accept');
            break;
            case "text/html":
            default:
            	return View::make('checkins.show')->with('checkins', $checkins);
            break;
		}


	}



	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public static function store()
	{
		$departure = Input::get('departure');

		if (!Sentry::check()) {
			return Redirect::to('login');
		} else {
	        $data = new Checkin;
	        $user = Sentry::getUser();

       		$data->user_id = $user->id;
        	$data->departure = $departure;

	        // $data->save;
			if (!CheckinController::isAlreadyCheckedIn($departure, $user)) {
				$data->save();
			}
	    }

	    $departure = str_replace("http://", "https://", $departure);

	    return Redirect::to($departure)->header('Access-Control-Allow-Origin', 'https://irail.dev');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public static function destroy($departure) {

		if (!Sentry::check()) {
			return Redirect::to('login');
		} else {
	        $user = Sentry::getUser();
	    	Checkin::where('user_id', $user->id)->where('departure', $departure)->delete();
	    }

	    $departure = str_replace("http://", "https://", $departure);

	    return Redirect::to('/checkins/', '303')->header('Access-Control-Allow-Origin', 'https://irail.dev');
	}

	public static function isAlreadyCheckedIn($departure, $user) {
		return (count(Checkin::where('user_id', $user->id)->where('departure', $departure)->first()) > 0);
	}

}