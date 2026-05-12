<?php

/**
 * Plugin Vehicle Scheduler for GLPI
 * 
 * English translation
 * 
 * @category Plugin
 * @package  VehicleScheduler
 * @author   Plugin Development Team
 * @license  PolyForm Noncommercial License 1.0.0
 */

$LANG['plugin_vehiclescheduler'] = [
    // Plugin / navigation
    'Vehicle Scheduler' => 'Planificador de vehículos',
    'Fleet Management' => 'Gestión de flotas',
    'Fleet Reservation' => 'Reserva de flota',
    'Dashboard' => 'Panel',
    'Calendar' => 'Calendario',
    'Management' => 'Gestión',
    'Administration' => 'Administración',
    'Reports' => 'Informes',
    'Settings' => 'Configuración',

    // Main entities
    'Vehicle' => 'Vehículo',
    'Vehicles' => 'Vehículos',
    'Driver' => 'Conductor',
    'Drivers' => 'Conductores',
    'Schedule' => 'Reserva',
    'Schedules' => 'Reservas',
    'Reservation' => 'Reserva',
    'Reservations' => 'Reservas',
    'Incident' => 'Incidente',
    'Incidents' => 'Incidentes',
    'Maintenance' => 'Mantenimiento',
    'Maintenances' => 'Mantenimientos',
    'Insurance Claim' => 'Siniestro',
    'Insurance Claims' => 'Siniestros',
    'Fine' => 'Multa',
    'Fines' => 'Multas',

    // Vehicle fields
    'Plate' => 'Matrícula',
    'Brand' => 'Marca',
    'Model' => 'Modelo',
    'Year' => 'Año',
    'Color' => 'Color',
    'Category' => 'Categoría',
    'Fuel' => 'Combustible',
    'Seats' => 'Asientos',
    'Mileage' => 'Kilometraje',
    'Status' => 'Estado',
    'Available' => 'Disponible',
    'Unavailable' => 'No disponible',
    'In maintenance' => 'En mantenimiento',

    // Schedule / reservation fields
    'Vehicle Scheduling' => 'Programación de vehículos',
    'Reservation Request' => 'Solicitud de reserva',
    'Reservation %s - %s' => 'Reserva %s - %s',
    'Requester' => 'Solicitante',
    'Request date' => 'Fecha de solicitud',
    'Start date' => 'Fecha de inicio',
    'End date' => 'Fecha de fin',
    'Start time' => 'Hora de salida',
    'End time' => 'Hora de regreso',
    'Departure' => 'Salida',
    'Return' => 'Retorno',
    'Origin' => 'Origen',
    'Destination' => 'Destino',
    'Purpose' => 'Finalidad',
    'Description' => 'Descripción',
    'Description/Purpose' => 'Descripción/Finalidad',
    'Describe the purpose of this reservation' => 'Describa la finalidad de esta reserva',
    'Number of passengers' => 'Número de pasajeros',
    'Passengers' => 'Pasajeros',
    'Assigned driver' => 'Conductor asignado',
    'Assigned vehicle' => 'Vehículo asignado',
    'Contact Phone' => 'Teléfono de contacto',
    'Department/Sector' => 'Departamento/Sector',
    'Additional Comments' => 'Comentarios adicionales',
    'Additional observations' => 'Observaciones adicionales',
    'Related Ticket' => 'Ticket relacionado',

    // Incident / report fields
    'Vehicle Report' => 'Reporte de vehículo',
    'Vehicle Reports' => 'Reportes de vehículo',
    'Report Type' => 'Tipo de reporte',
    'Report Date' => 'Fecha del reporte',
    'Reporter' => 'Reportado por',
    'Maintenance Needed' => 'Requiere mantenimiento',
    'Problem/Issue' => 'Problema/Incidencia',
    'Accident' => 'Accidente',
    'Observation' => 'Observación',
    'Describe the issue, observation or situation in detail' => 'Describa el problema, la observación o la situación en detalle',

    // Statuses
    'New' => 'Nuevo',
    'Pending' => 'Pendiente',
    'Approved' => 'Aprobado',
    'Rejected' => 'Rechazado',
    'Cancelled' => 'Cancelado',
    'Completed' => 'Completado',
    'Active' => 'Activo',
    'Closed' => 'Cerrado',

    // Actions
    'Open' => 'Abrir',
    'View' => 'Ver',
    'Add' => 'Agregar',
    'Create' => 'Crear',
    'Edit' => 'Editar',
    'Update' => 'Actualizar',
    'Save' => 'Guardar',
    'Delete' => 'Eliminar',
    'Approve' => 'Aprobar',
    'Reject' => 'Rechazar',
    'Cancel' => 'Cancelar',
    'Close' => 'Cerrar',
    'Filter' => 'Filtrar',
    'Search' => 'Buscar',
    'Clear filters' => 'Limpiar filtros',

    // Dashboard / KPI
    'Approved reservations' => 'Reservas aprobadas',
    'Pending requests' => 'Solicitudes pendientes',
    'Rejected requests' => 'Solicitudes rechazadas',
    'Reservations by status' => 'Reservas por estado',
    'Operational overview' => 'Vista operativa',
    'Management overview' => 'Vista de gestión',
    'Executive overview' => 'Vista ejecutiva',

    // Validation messages
    'Department is required' => 'El departamento es obligatorio',
    'Contact phone is required' => 'El teléfono de contacto es obligatorio',
    'Description/Purpose is required' => 'La descripción/finalidad es obligatoria',
    'Description is required' => 'La descripción es obligatoria',
    'Vehicle is required' => 'El vehículo es obligatorio',
    'Start date is required' => 'La fecha de inicio es obligatoria',
    'End date is required' => 'La fecha de fin es obligatoria',
    'Destination is required' => 'El destino es obligatorio',
    'Driver is required' => 'El conductor es obligatorio',
    'Invalid period' => 'Período inválido',
    'The selected vehicle is not available for this period' => 'El vehículo seleccionado no está disponible para este período',
    'The selected driver is not available for this period' => 'El conductor seleccionado no está disponible para este período',

    // Success / error messages
    'Item added successfully' => 'Elemento agregado con éxito',
    'Item updated successfully' => 'Elemento actualizado con éxito',
    'Item deleted successfully' => 'Elemento eliminado con éxito',
    'Reservation approved successfully' => 'Reserva aprobada con éxito',
    'Reservation rejected successfully' => 'Reserva rechazada con éxito',
    'Unable to save data' => 'No se pudieron guardar los datos',
    'An unexpected error occurred' => 'Ocurrió un error inesperado',

    // Notifications / tickets
    'Vehicle Reservation: %s - %s' => 'Reserva de vehículo: %s - %s',
    'Vehicle Reservation Details:\n\nVehicle: %s\nRequester: %s\nStart: %s\nEnd: %s\nDestination: %s\nPassengers: %d\nPurpose: %s'
    => 'Detalles de la reserva de vehículo:\n\nVehículo: %s\nSolicitante: %s\nInicio: %s\nFin: %s\nDestino: %s\nPasajeros: %d\nFinalidad: %s',
];
