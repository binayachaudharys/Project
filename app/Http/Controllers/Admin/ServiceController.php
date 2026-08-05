<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Models\Service;
use App\Repositories\ServiceRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(ServiceRepository $services): Response
    {
        return Inertia::render('Admin/Services/Index', [
            'services' => $services->allOrdered(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Services/Form', [
            'service' => null,
        ]);
    }

    public function store(StoreServiceRequest $request, ServiceRepository $services): RedirectResponse
    {
        $services->createService($request->validated());

        return redirect()->route('admin.services.index')->with('success', 'Service created.');
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('Admin/Services/Form', [
            'service' => $service,
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service, ServiceRepository $services): RedirectResponse
    {
        $services->updateService($service, $request->validated());

        return redirect()->route('admin.services.index')->with('success', 'Service updated.');
    }

    public function destroy(Service $service, ServiceRepository $services): RedirectResponse
    {
        $services->deleteService($service);

        return redirect()->route('admin.services.index')->with('success', 'Service deleted.');
    }
}
