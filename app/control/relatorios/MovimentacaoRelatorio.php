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

class MovimentacaoRelatorio extends TPage
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
        $this->form = new BootstrapFormBuilder('form_movimentacao_report');
        $this->form->setFormTitle('Movimentações do Estoque');

        // create the form fields
        $produto      = new TDBUniqueSearch('produto_id', 'sample', 'Produto', 'id', 'nome');
        $output_type = new TRadioGroup('output_type');
    


        $this->form->addFields([new TLabel('Produto')], [$produto]);
        $this->form->addFields([new TLabel('Formato')], [$output_type]);

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

            $repository = new TRepository('Movimentacoes');
            $criteria = new TCriteria;
            
            if ($data->produto_id) {
                $criteria->add(new TFilter('produto_id', '=', $data->produto_id));
            }
          
     



            $mov = $repository->load($criteria);
            $format = $data->output_type;

            if ($mov) {
                $widths = array(80, 150, 90, 40, 60, 60, 60);

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
                    $table->addStyle('header', 'Helvetica', '14', 'B', '#ffffff', '#4B5D8E');
                    $table->addStyle('title', 'Helvetica', '8', 'B', '#ffffff', '#617FC3');
                    $table->addStyle('datap', 'Helvetica', '7', '', '#000000', '#E3E3E3', 'LR');
                    $table->addStyle('datai', 'Helvetica', '7', '', '#000000', '#ffffff', 'LR');
                    $table->addStyle('footer', 'Helvetica', '10', '', '#2B2B2B', '#B4CAFF');

                    $table->setHeaderCallback(function ($table) {
                        $table->addRow();
                        $table->addCell('Movimentação', 'center', 'header', 7);

                        $table->addRow();
                        $table->addCell('Data e Hora', 'center', 'title',1);
                        $table->addCell('Descrição', 'center', 'title',1);
                        $table->addCell('Produto', 'center', 'title',1);
                        $table->addCell('Quant.', 'center', 'title',1);
                        $table->addCell('Preço', 'center', 'title',1);
                        $table->addCell('Estoque Atual', 'center', 'title',1);
                        $table->addCell('Responsável', 'center', 'title',1);
                    });

                    $table->setFooterCallback(function ($table) {
                        $table->addRow();
                        $table->addCell(date('Y-m-d h:i:s'), 'center', 'footer', 7);
                    });

                    $colour = FALSE;

                    foreach ($mov as $movs) {
                        $style = $colour ? 'datap' : 'datai';
                        $table->addRow();
                        $table->addCell($movs->data_hora, 'center', $style);
                        $table->addCell($movs->descricao, 'center', $style);
                        $table->addCell($movs->produto->nome, 'center', $style, );
                        $table->addCell($movs->quantidade, 'center', $style);
                        $table->addCell('R$ '.$movs->preco_unit, 'center', $style);
                        $table->addCell('R$ '.$movs->saldo_anterior, 'center', $style);
                        $table->addCell($movs->user->name, 'center', $style);

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
