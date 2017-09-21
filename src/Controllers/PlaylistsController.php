<?php

namespace Railroad\Railcontent\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Railcontent\Services\PlaylistsService;

class PlaylistsController extends Controller
{
    protected $playlistsService;

    /**
     * PlaylistsController constructor.
     * @param $playlistsService
     */
    public function __construct(PlaylistsService $playlistsService)
    {
        $this->playlistsService = $playlistsService;
    }

    /** Call the method from service that add content to playlist
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToPlaylist(Request $request)
    {
        $playlist = $this->playlistsService->addToPlaylist(
            $request->input('content_id'),
            $request->input('playlist_id')
        );

        return response()->json($playlist, 200);
    }
}