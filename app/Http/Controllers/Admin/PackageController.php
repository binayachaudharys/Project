<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePackageRequest;
use App\Http\Requests\Admin\UpdatePackageRequest;
use App\Models\Package;
use App\Repositories\PackageRepository;
use App\Repositories\ServiceRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    public function index(PackageRepository $packages): Response
    {
        return Inertia::render('Admin/Packages/Index', [
            'packages' => $packages->allWithServices(),
        ]);
    }

    public function create(ServiceRepository $services): Response
    {
        return Inertia::render('Admin/Packages/Form', [
            'package' => null,
            'services' => $services->allOrdered(),
        ]);
    }

    public function store(StorePackageRequest $request, PackageRepository $packages): RedirectResponse
    {
        $packages->createPackage($request->validated());

        return redirect()->route('admin.packages.index')->with('success', 'Package created.');
    }

    public function edit(Package $package, ServiceRepository $services): Response
    {
        $package->load('services:id,name');

        return Inertia::render('Admin/Packages/Form', [
            'package' => $package,
            'services' => $services->allOrdered(),
        ]);
    }

    public function update(UpdatePackageRequest $request, Package $package, PackageRepository $packages): RedirectResponse
    {
        $packages->updatePackage($package, $request->validated());

        return redirect()->route('admin.packages.index')->with('success', 'Package updated.');
    }

    public function destroy(Package $package, PackageRepository $packages): RedirectResponse
    {
        $packages->deletePackage($package);

        return redirect()->route('admin.packages.index')->with('success', 'Package deleted.');
    }
}
