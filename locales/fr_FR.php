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
    'Vehicle Scheduler' => 'Planificateur de véhicules',
    'Fleet Management' => 'Gestion de flotte',
    'Fleet Reservation' => 'Réservation de flotte',
    'Dashboard' => 'Tableau de bord',
    'Calendar' => 'Calendrier',
    'Management' => 'Gestion',
    'Administration' => 'Administration',
    'Reports' => 'Rapports',
    'Settings' => 'Paramètres',

    // Main entities
    'Vehicle' => 'Véhicule',
    'Vehicles' => 'Véhicules',
    'Driver' => 'Conducteur',
    'Drivers' => 'Conducteurs',
    'Schedule' => 'Réservation',
    'Schedules' => 'Réservations',
    'Reservation' => 'Réservation',
    'Reservations' => 'Réservations',
    'Incident' => 'Incident',
    'Incidents' => 'Incidents',
    'Maintenance' => 'Maintenance',
    'Maintenances' => 'Maintenances',
    'Insurance Claim' => 'Sinistre',
    'Insurance Claims' => 'Sinistres',
    'Fine' => 'Amende',
    'Fines' => 'Amendes',

    // Vehicle fields
    'Plate' => 'Plaque d’immatriculation',
    'Brand' => 'Marque',
    'Model' => 'Modèle',
    'Year' => 'Année',
    'Color' => 'Couleur',
    'Category' => 'Catégorie',
    'Fuel' => 'Carburant',
    'Seats' => 'Places',
    'Mileage' => 'Kilométrage',
    'Status' => 'Statut',
    'Available' => 'Disponible',
    'Unavailable' => 'Indisponible',
    'In maintenance' => 'En maintenance',

    // Schedule / reservation fields
    'Vehicle Scheduling' => 'Planification des véhicules',
    'Reservation Request' => 'Demande de réservation',
    'Reservation %s - %s' => 'Réservation %s - %s',
    'Requester' => 'Demandeur',
    'Request date' => 'Date de la demande',
    'Start date' => 'Date de début',
    'End date' => 'Date de fin',
    'Start time' => 'Heure de départ',
    'End time' => 'Heure de retour',
    'Departure' => 'Départ',
    'Return' => 'Retour',
    'Origin' => 'Origine',
    'Destination' => 'Destination',
    'Purpose' => 'Motif',
    'Description' => 'Description',
    'Description/Purpose' => 'Description/Motif',
    'Describe the purpose of this reservation' => 'Décrivez le motif de cette réservation',
    'Number of passengers' => 'Nombre de passagers',
    'Passengers' => 'Passagers',
    'Assigned driver' => 'Conducteur affecté',
    'Assigned vehicle' => 'Véhicule affecté',
    'Contact Phone' => 'Téléphone de contact',
    'Department/Sector' => 'Département/Secteur',
    'Additional Comments' => 'Commentaires supplémentaires',
    'Additional observations' => 'Observations supplémentaires',
    'Related Ticket' => 'Ticket lié',

    // Incident / report fields
    'Vehicle Report' => 'Rapport de véhicule',
    'Vehicle Reports' => 'Rapports de véhicule',
    'Report Type' => 'Type de rapport',
    'Report Date' => 'Date du rapport',
    'Reporter' => 'Déclarant',
    'Maintenance Needed' => 'Maintenance nécessaire',
    'Problem/Issue' => 'Problème/Incident',
    'Accident' => 'Accident',
    'Observation' => 'Observation',
    'Describe the issue, observation or situation in detail' => 'Décrivez le problème, l’observation ou la situation en détail',

    // Statuses
    'New' => 'Nouveau',
    'Pending' => 'En attente',
    'Approved' => 'Approuvé',
    'Rejected' => 'Rejeté',
    'Cancelled' => 'Annulé',
    'Completed' => 'Terminé',
    'Active' => 'Actif',
    'Closed' => 'Clos',

    // Actions
    'Open' => 'Ouvrir',
    'View' => 'Voir',
    'Add' => 'Ajouter',
    'Create' => 'Créer',
    'Edit' => 'Modifier',
    'Update' => 'Mettre à jour',
    'Save' => 'Enregistrer',
    'Delete' => 'Supprimer',
    'Approve' => 'Approuver',
    'Reject' => 'Rejeter',
    'Cancel' => 'Annuler',
    'Close' => 'Fermer',
    'Filter' => 'Filtrer',
    'Search' => 'Rechercher',
    'Clear filters' => 'Effacer les filtres',

    // Dashboard / KPI
    'Approved reservations' => 'Réservations approuvées',
    'Pending requests' => 'Demandes en attente',
    'Rejected requests' => 'Demandes rejetées',
    'Reservations by status' => 'Réservations par statut',
    'Operational overview' => 'Vue opérationnelle',
    'Management overview' => 'Vue de gestion',
    'Executive overview' => 'Vue exécutive',

    // Validation messages
    'Department is required' => 'Le département est obligatoire',
    'Contact phone is required' => 'Le téléphone de contact est obligatoire',
    'Description/Purpose is required' => 'La description/le motif est obligatoire',
    'Description is required' => 'La description est obligatoire',
    'Vehicle is required' => 'Le véhicule est obligatoire',
    'Start date is required' => 'La date de début est obligatoire',
    'End date is required' => 'La date de fin est obligatoire',
    'Destination is required' => 'La destination est obligatoire',
    'Driver is required' => 'Le conducteur est obligatoire',
    'Invalid period' => 'Période invalide',
    'The selected vehicle is not available for this period' => 'Le véhicule sélectionné n’est pas disponible pour cette période',
    'The selected driver is not available for this period' => 'Le conducteur sélectionné n’est pas disponible pour cette période',

    // Success / error messages
    'Item added successfully' => 'Élément ajouté avec succès',
    'Item updated successfully' => 'Élément mis à jour avec succès',
    'Item deleted successfully' => 'Élément supprimé avec succès',
    'Reservation approved successfully' => 'Réservation approuvée avec succès',
    'Reservation rejected successfully' => 'Réservation rejetée avec succès',
    'Unable to save data' => 'Impossible d’enregistrer les données',
    'An unexpected error occurred' => 'Une erreur inattendue est survenue',

    // Notifications / tickets
    'Vehicle Reservation: %s - %s' => 'Réservation de véhicule : %s - %s',
    'Vehicle Reservation Details:\n\nVehicle: %s\nRequester: %s\nStart: %s\nEnd: %s\nDestination: %s\nPassengers: %d\nPurpose: %s'
    => 'Détails de la réservation de véhicule :\n\nVéhicule : %s\nDemandeur : %s\nDébut : %s\nFin : %s\nDestination : %s\nPassagers : %d\nMotif : %s',
];
