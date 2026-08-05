<?php

namespace App\Repositories;

use App\Models\Appointment;
use Jsdecena\Baserepo\BaseRepository;
use LogicException;

class AppointmentRepository extends BaseRepository
{
    public function __construct(Appointment $model)
    {
        parent::__construct($model);
    }

    /**
     * Create a customer booking with overlap validation.
     *
     * Implemented in a later task (booking flow).
     */
    public function createBooking(array $data): Appointment
    {
        throw new LogicException('AppointmentRepository::createBooking() is not implemented yet.');
    }
}
