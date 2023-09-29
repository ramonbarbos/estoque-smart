<?php
use Adianti\Database\TRecord;

class Item_Saida extends TRecord
{
    const TABLENAME = 'item_saida';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);

        // Adicione os atributos normais
        parent::addAttribute('saida_id');
        parent::addAttribute('produto_id');
        parent::addAttribute('quantidade');
        parent::addAttribute('preco_unit');
        parent::addAttribute('total');
      
    }

    public function get_produto()
    {
        return Produto::find($this->produto_id);
    }

  
}
