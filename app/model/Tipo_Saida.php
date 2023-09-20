<?php

use Adianti\Database\TRecord;

/**
 * Grupo Active Record
 * @author  <your-name-here>
 */
class Tipo_Saida extends TRecord
{
    const TABLENAME = 'tipo_saida';
    const PRIMARYKEY = 'id';
    const IDPOLICY =  'max'; // {max, serial}


    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
       
    }



}
