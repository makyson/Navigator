<?php

declare(strict_types=1);

namespace App\core\router\net;

use Exception;

/**
 * Classe UploadedFile
 *
 * Representa um arquivo enviado por meio de um formulário HTML, encapsulando
 * informações como o nome, tipo MIME, tamanho, nome temporário e código de erro.
 */
class UploadedFile
{
    /**
     * Nome original do arquivo no cliente.
     *
     * @var string
     */
    private string $name;

    /**
     * Tipo MIME do arquivo.
     *
     * @var string
     */
    private string $mimeType;

    /**
     * Tamanho do arquivo em bytes.
     *
     * @var int
     */
    private int $size;

    /**
     * Nome temporário do arquivo no servidor.
     *
     * @var string
     */
    private string $tmpName;

    /**
     * Código de erro associado ao envio do arquivo.
     *
     * @var int
     */
    private int $error;

    /**
     * Construtor.
     *
     * Inicializa a instância de `UploadedFile` com informações do arquivo enviado.
     *
     * @param string $name Nome original do arquivo.
     * @param string $mimeType Tipo MIME do arquivo.
     * @param int $size Tamanho do arquivo em bytes.
     * @param string $tmpName Nome temporário do arquivo no servidor.
     * @param int $error Código de erro associado ao envio do arquivo.
     */
    public function __construct(string $name, string $mimeType, int $size, string $tmpName, int $error)
    {
        $this->name = $name;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->tmpName = $tmpName;
        $this->error = $error;
    }

    /**
     * Obtém o nome original do arquivo enviado pelo cliente.
     *
     * @return string Nome do arquivo.
     */
    public function getClientFilename(): string
    {
        return $this->name;
    }

    /**
     * Obtém o tipo MIME informado pelo cliente.
     *
     * @return string Tipo MIME do arquivo.
     */
    public function getClientMediaType(): string
    {
        return $this->mimeType;
    }

    /**
     * Obtém o tamanho do arquivo enviado.
     *
     * @return int Tamanho do arquivo em bytes.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Obtém o nome temporário do arquivo no servidor.
     *
     * @return string Nome temporário do arquivo.
     */
    public function getTempName(): string
    {
        return $this->tmpName;
    }

    /**
     * Obtém o código de erro associado ao envio do arquivo.
     *
     * @return int Código de erro.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Move o arquivo enviado para um local especificado.
     *
     * @param string $targetPath Caminho de destino para o arquivo.
     *
     * @return void
     * @throws Exception Se houver erros ao mover o arquivo.
     */
    public function moveTo(string $targetPath): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($this->error));
        }

        $isUploadedFile = is_uploaded_file($this->tmpName);
        if (
            $isUploadedFile &&
            !move_uploaded_file($this->tmpName, $targetPath)
        ) {
            throw new Exception('Cannot move uploaded file'); // @codeCoverageIgnore
        } elseif (!$isUploadedFile && getenv('PHPUNIT_TEST')) {
            rename($this->tmpName, $targetPath);
        }
    }

    /**
     * Obtém a mensagem de erro associada a um código de erro de upload.
     *
     * @param int $error Código de erro de upload.
     *
     * @return string Mensagem de erro correspondente.
     */
    protected function getUploadErrorMessage(int $error): string
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'An unknown error occurred. Error code: ' . $error;
        }
    }
}
