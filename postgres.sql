-- Database Schema for the Bird and Lime rideshare MDS data feeds
set search_path=mds;

create type vehicle_types      as enum('bicycle', 'car', 'scooter');
create type propulsion_types   as enum('human', 'electric_assist', 'electric', 'combustion');
create type event_types        as enum('available', 'reserved', 'unavailable', 'removed');
create type event_type_reasons as enum(
    'service_start',
    'maintenance_drop_off',
    'rebalance_drop_off',
    'user_drop_off',
    'user_pick_up',
    'maintenance',
    'low_battery',
    'service_end',
    'rebalance_pick_up',
    'maintenance_pick_up'
);


create table trips (
    provider_id              uuid          not null,
    provider_name            varchar(32)   not null,
    device_id                uuid          not null,
    vehicle_id               varchar(128)  not null,
    vehicle_type             vehicle_types not null,
    propulsion_type          jsonb         not null,
    trip_id                  uuid          not null primary key,
    trip_duration            int           not null,
    trip_distance            int           not null,
    route                    jsonb         not null,
    accuracy                 int           not null,
    start_time               timestamptz   not null,
    end_time                 timestamptz   not null,
    publication_time         timestamptz,
    parking_verification_url varchar(128),
    standard_cost            int,
    actual_cost              int,
    currency                 varchar(3)
);

create table status_changes (
    provider_id              uuid               not null,
    provider_name            varchar(32)        not null,
    device_id                uuid               not null,
    vehicle_id               varchar(128)       not null,
    vehicle_type             vehicle_types      not null,
    propulsion_type          jsonb              not null,
    event_type               event_types        not null,
    event_type_reason        event_type_reasons not null,
    event_time               timestamptz        not null,
    publication_time         timestamptz,
    event_location           jsonb              not null,
    battery_pct              real,
    associated_trip          uuid,
    associated_ticket        varchar(32),
    primary key (device_id, event_time),
    foreign key (associated_trip) references trips(trip_id)
);
