<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TRadioGroup;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class EstoqueRelatorio extends TPage
{
    private $form; // form

    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {
        parent::__construct();

        // creates the form
        $this->form = new BootstrapFormBuilder('form_cliente_report');
        $this->form->setFormTitle('Mapa de Estoque');

        // create the form fields
        $nf      = new TDBUniqueSearch('nota_fiscal', 'sample', 'Estoque', 'nota_fiscal', 'nota_fiscal');
        $produto      = new TDBUniqueSearch('produto_id', 'sample', 'Produto', 'id', 'nome');
        $output_type = new TRadioGroup('output_type');
        $quantidade = new TRadioGroup('quantidade');
        $quantidade->setUseButton();
        $options = [ '1' => 'Sim', '0' => 'Não', '3' => 'Geral'];
        $quantidade->addItems($options);
      //  $show_in_stock->setValue('2'); // Valor padrão
        $quantidade->setLayout('horizontal');

        $this->form->addFields([new TLabel('Nota Fiscal')], [$nf]);
        $this->form->addFields([new TLabel('Produto')], [$produto]);
        $this->form->addFields([new TLabel('Com Estoque')], [$quantidade]);
        $this->form->addFields([new TLabel('Formato')], [$output_type]);

        $nf->setSize('80%');
        $nf->setMinLength(0);
        $produto->setSize('80%');
        $produto->setMinLength(0);
        $output_type->setUseButton();
        $options = ['html' => 'HTML', 'pdf' => 'PDF', 'rtf' => 'RTF', 'xls' => 'XLS'];
        $output_type->addItems($options);
        $output_type->setValue('pdf');
        $output_type->setLayout('horizontal');

        $this->form->addAction('Generate', new TAction([$this, 'onGenerate']), 'fa:download blue');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    /**
     * method onGenerate()
     * Executed whenever the user clicks at the generate button
     */
    function onGenerate()
    {
        try {
            TTransaction::open('sample');

            $data = $this->form->getData();

            $repository = new TRepository('Estoque');
            $criteria = new TCriteria;
            if ($data->nota_fiscal) {
                $criteria->add(new TFilter('nota_fiscal', 'like', "%{$data->nota_fiscal}%"));
            }
            if ($data->produto_id) {
                $criteria->add(new TFilter('produto_id', '=', $data->produto_id));
            }

                        
            // Adicione este trecho para aplicar o filtro de quantidade em estoque
            if ($data->quantidade == '1') {
                $criteria->add(new TFilter('quantidade', '>', 0));
            } elseif ($data->quantidade == '0') {
                $criteria->add(new TFilter('quantidade', '=', 0));
            }elseif ($data->quantidade == '0') {
                $criteria->add(new TFilter('quantidade', '>=', 0));
            }




            $estoque = $repository->load($criteria);
            $format = $data->output_type;

            if ($estoque) {
                $widths = array(60, 200, 80, 120, 80);

                switch ($format) {
                    case 'html':
                        $table = new TTableWriterHTML($widths);
                        break;
                    case 'pdf':
                        $table = new TTableWriterPDF($widths);
                        break;
                    case 'rtf':
                        $table = new TTableWriterRTF($widths);
                        break;
                    case 'xls':
                        $table = new TTableWriterXLS($widths);
                        break;
                }

                if (!empty($table)) {
                    $table->addStyle('header', 'Helvetica', '16', 'B', '#ffffff', '#4B5D8E');
                    $table->addStyle('title', 'Helvetica', '10', 'B', '#ffffff', '#617FC3');
                    $table->addStyle('datap', 'Helvetica', '10', '', '#000000', '#E3E3E3', 'LR');
                    $table->addStyle('datai', 'Helvetica', '10', '', '#000000', '#ffffff', 'LR');
                    $table->addStyle('footer', 'Helvetica', '10', '', '#2B2B2B', '#B4CAFF');

                    $table->setHeaderCallback(function ($table) {
                        $table->addRow();
                        $table->addCell('Estoque', 'center', 'header', 5);

                        $table->addRow();
                        $table->addCell('Nota Fiscal', 'center', 'title');
                        $table->addCell('Item', 'center', 'title');
                        $table->addCell('Estoque', 'center', 'title');
                        $table->addCell('Valor Unidade', 'center', 'title');
                        $table->addCell('Total', 'center', 'title');
                    });

                    $table->setFooterCallback(function ($table) {
                        $table->addRow();
                        $table->addCell(date('Y-m-d h:i:s'), 'center', 'footer', 5);
                    });

                    $colour = FALSE;

                    foreach ($estoque as $estoques) {
                        $style = $colour ? 'datap' : 'datai';
                        $table->addRow();
                        $table->addCell($estoques->nota_fiscal, 'center', $style);
                        $table->addCell($estoques->produto->nome, 'center', $style);
                        $table->addCell($estoques->quantidade, 'center', $style);
                        $table->addCell($estoques->preco_unit, 'center', $style);
                        $table->addCell($estoques->valor_total, 'center', $style);

                        $colour = !$colour;
                    }

                    $output = "app/output/cliente_tabular.{$format}";

                    if (!file_exists($output) || is_writable($output)) {
                        $table->save($output);
                        parent::openFile($output);
                    } else {
                        throw new Exception(_t('Permission denied') . ': ' . $output);
                    }

                    new TMessage('info', "Relatório gerado. Por favor, permita pop-ups no navegador. <br> <a href='$output'>Clique aqui para download</a>");
                }
            } else {
                new TMessage('error', 'Nenhum registro encontrado');
            }

            $this->form->setData($data);

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
