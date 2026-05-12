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
    'Vehicle Scheduler' => 'Agendador de Veículos',
    'Fleet Management' => 'Gestão de Frota',
    'Fleet Reservation' => 'Reserva de Frota',
    'Dashboard' => 'Dashboard',
    'Calendar' => 'Calendário',
    'Management' => 'Gestão',
    'Administration' => 'Administração',
    'Reports' => 'Relatórios',
    'Settings' => 'Configurações',

    // Main entities
    'Vehicle' => 'Veículo',
    'Vehicles' => 'Veículos',
    'Driver' => 'Motorista',
    'Drivers' => 'Motoristas',
    'Schedule' => 'Reserva',
    'Schedules' => 'Reservas',
    'Reservation' => 'Reserva',
    'Reservations' => 'Reservas',
    'Incident' => 'Incidente',
    'Incidents' => 'Incidentes',
    'Maintenance' => 'Manutenção',
    'Maintenances' => 'Manutenções',
    'Insurance Claim' => 'Sinistro',
    'Insurance Claims' => 'Sinistros',
    'Fine' => 'Multa',
    'Fines' => 'Multas',

    // Vehicle fields
    'Plate' => 'Placa',
    'Brand' => 'Marca',
    'Model' => 'Modelo',
    'Year' => 'Ano',
    'Color' => 'Cor',
    'Category' => 'Categoria',
    'Fuel' => 'Combustível',
    'Seats' => 'Assentos',
    'Mileage' => 'Quilometragem',
    'Status' => 'Status',
    'Available' => 'Disponível',
    'Unavailable' => 'Indisponível',
    'In maintenance' => 'Em manutenção',

    // Schedule / reservation fields
    'Vehicle Scheduling' => 'Agendamento de Veículos',
    'Reservation Request' => 'Solicitação de Reserva',
    'Reservation %s - %s' => 'Reserva %s - %s',
    'Requester' => 'Solicitante',
    'Request date' => 'Data da solicitação',
    'Start date' => 'Data inicial',
    'End date' => 'Data final',
    'Start time' => 'Hora de saída',
    'End time' => 'Hora de retorno',
    'Departure' => 'Saída',
    'Return' => 'Retorno',
    'Origin' => 'Origem',
    'Destination' => 'Destino',
    'Purpose' => 'Finalidade',
    'Description' => 'Descrição',
    'Description/Purpose' => 'Descrição/Finalidade',
    'Describe the purpose of this reservation' => 'Descreva a finalidade desta reserva',
    'Number of passengers' => 'Número de passageiros',
    'Passengers' => 'Passageiros',
    'Assigned driver' => 'Motorista atribuído',
    'Assigned vehicle' => 'Veículo atribuído',
    'Contact Phone' => 'Telefone de contato',
    'Department/Sector' => 'Departamento/Setor',
    'Additional Comments' => 'Comentários adicionais',
    'Additional observations' => 'Observações adicionais',
    'Related Ticket' => 'Chamado relacionado',

    // Incident / report fields
    'Vehicle Report' => 'Relato de Veículo',
    'Vehicle Reports' => 'Relatos de Veículo',
    'Report Type' => 'Tipo de relato',
    'Report Date' => 'Data do relato',
    'Reporter' => 'Responsável pelo relato',
    'Maintenance Needed' => 'Necessita manutenção',
    'Problem/Issue' => 'Problema/Ocorrência',
    'Accident' => 'Acidente',
    'Observation' => 'Observação',
    'Describe the issue, observation or situation in detail' => 'Descreva o problema, observação ou situação em detalhe',

    // Statuses
    'New' => 'Novo',
    'Pending' => 'Pendente',
    'Approved' => 'Aprovado',
    'Rejected' => 'Rejeitado',
    'Cancelled' => 'Cancelado',
    'Completed' => 'Concluído',
    'Active' => 'Ativo',
    'Closed' => 'Fechado',

    // Actions
    'Open' => 'Abrir',
    'View' => 'Visualizar',
    'Add' => 'Adicionar',
    'Create' => 'Criar',
    'Edit' => 'Editar',
    'Update' => 'Atualizar',
    'Save' => 'Salvar',
    'Delete' => 'Excluir',
    'Approve' => 'Aprovar',
    'Reject' => 'Recusar',
    'Cancel' => 'Cancelar',
    'Close' => 'Fechar',
    'Filter' => 'Filtrar',
    'Search' => 'Pesquisar',
    'Clear filters' => 'Limpar filtros',

    // Dashboard / KPI
    'Approved reservations' => 'Reservas aprovadas',
    'Pending requests' => 'Solicitações pendentes',
    'Rejected requests' => 'Solicitações rejeitadas',
    'Reservations by status' => 'Reservas por status',
    'Operational overview' => 'Visão operacional',
    'Management overview' => 'Visão gerencial',
    'Executive overview' => 'Visão executiva',

    // Validation messages
    'Department is required' => 'Departamento é obrigatório',
    'Contact phone is required' => 'Telefone de contato é obrigatório',
    'Description/Purpose is required' => 'Descrição/Finalidade é obrigatória',
    'Description is required' => 'Descrição é obrigatória',
    'Vehicle is required' => 'Veículo é obrigatório',
    'Start date is required' => 'Data inicial é obrigatória',
    'End date is required' => 'Data final é obrigatória',
    'Destination is required' => 'Destino é obrigatório',
    'Driver is required' => 'Motorista é obrigatório',
    'Invalid period' => 'Período inválido',
    'The selected vehicle is not available for this period' => 'O veículo selecionado não está disponível para este período',
    'The selected driver is not available for this period' => 'O motorista selecionado não está disponível para este período',

    // Success / error messages
    'Item added successfully' => 'Item adicionado com sucesso',
    'Item updated successfully' => 'Item atualizado com sucesso',
    'Item deleted successfully' => 'Item excluído com sucesso',
    'Reservation approved successfully' => 'Reserva aprovada com sucesso',
    'Reservation rejected successfully' => 'Reserva recusada com sucesso',
    'Unable to save data' => 'Não foi possível salvar os dados',
    'An unexpected error occurred' => 'Ocorreu um erro inesperado',

    // Notifications / tickets
    'Vehicle Reservation: %s - %s' => 'Reserva de Veículo: %s - %s',
    'Vehicle Reservation Details:\n\nVehicle: %s\nRequester: %s\nStart: %s\nEnd: %s\nDestination: %s\nPassengers: %d\nPurpose: %s'
    => 'Detalhes da Reserva de Veículo:\n\nVeículo: %s\nSolicitante: %s\nInício: %s\nFim: %s\nDestino: %s\nPassageiros: %d\nFinalidade: %s',
];
