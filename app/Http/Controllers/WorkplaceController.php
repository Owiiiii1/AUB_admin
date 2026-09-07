<?php

namespace App\Http\Controllers;

use App\Support\RoleAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkplaceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $menuItems = RoleAccess::menuItemsForUser($user);

        return Inertia::render('Workplace/Index', [
            'menuItems' => $menuItems,
            'roleName' => $user?->role?->name,
        ]);
    }
}
