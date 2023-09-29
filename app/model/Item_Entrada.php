<?php
use Adianti\Database\TRecord;

class Item_Entrada extends TRecord
{
    const TABLENAME = 'item_entrada';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);

        // Adicione os atributos normais
        parent::addAttribute('entrada_id');
        parent::addAttribute('produto_id');
        parent::addAttribute('quantidade');
        parent::addAttribute('preco_unit');
        parent::addAttribute('total');
        parent::addAttribute('updated_at');
        parent::addAttribute('created_at');


    }

    public function get_produto()
    {
        return Produto::find($this->produto_id);
    }
    public function get_fornecedor()
    {
        return Fornecedor::find($this->fornecedor_id);
    }
    public function get_tipo()
    {
        return Tipo_Entrada::find($this->tp_entrada);
    }
}
