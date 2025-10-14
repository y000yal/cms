<?php

namespace App\Interfaces;

/**
 * BaseInterface
 * App\Interfaces
 * @author   Yoyal Limbu
 * @date     14-10-2025 : 08:39 PM
 */
interface  BaseInterface {
    public function getAll(array $parameter, $path );

    public function create( array $data );

    public function insert( array $data );

    public function update( $id, array $data );

    public function delete( $id );

    public function getSpecificById( $id );

    public function getSpecificByColumnValue( $column, $value );

    public function deleteMultipleByColumnValue( $column, array $values );

    public function getSpecificByIdOrSlug( $id );


    public function createNewSlug( $name );

}
