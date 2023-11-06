<?php

namespace Debva\Nix;

class Document
{
    public function __get($property)
    {
        switch ($property) {
            case 'pdf':
                return $this->pdf();
        }
    }

    protected function pdf()
    {
        $class = new Anonymous;

        $class->flag = 'S';

        $class->macro('init', function () {
            return new \setasign\Fpdi\Fpdi;
        });


        $class->macro('merge', function ($self, $output, ...$files) {
            if (empty($files)) {
                return null;
            }

            if (is_array(reset($files))) {
                $files = reset($files);
            }

            $pdf = $self->init();

            foreach ($files as $file) {
                $pdf->addPage();
                $pdf->setSourceFile($file);
                $pdf->useTemplate($pdf->importPage(1));
            }

            return $pdf->output($output, $self->flag);
        });

        return $class;
    }
}
