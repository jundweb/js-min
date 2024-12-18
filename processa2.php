<?php

ini_set('display_errors', 1);
ini_set('log_errors', 1); 
ini_set('error_log', 'php_error.log'); 
error_reporting(E_ALL); 

require 'vendor/autoload.php';

if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {

    // Verifica se o arquivo é uma imagem válida
    $imagemTemp = $_FILES['imagem']['tmp_name'];
    $imageInfo = getimagesize($imagemTemp);

    if ($imageInfo === false) {
        echo json_encode(["success" => false, "message" => "O arquivo enviado não é uma imagem válida."]);
        exit;
    }

    // Chave Cloud Vision
    $credentialsPath = 'carteirinha-443818-72b717234e3c.json';

    if (!file_exists($credentialsPath)) {
        echo json_encode(["success" => false, "message" => "Arquivo de credenciais não encontrado."]);
        exit;
    }

    $vision = new Google\Cloud\Vision\V1\ImageAnnotatorClient([
        'credentials' => $credentialsPath
    ]);


    $imagem = file_get_contents($imagemTemp);
    $image = (new Google\Cloud\Vision\V1\Image())->setContent($imagem);
    

    try {
        $response = $vision->faceDetection($image);
        $faces = $response->getFaceAnnotations();
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Erro ao processar a imagem: " . $e->getMessage()]);
        exit;
    }

    $vision->close();

    // Verifica se foi encontrado algum rosto
    if (count($faces) > 0) {
        $face = $faces[0]; 

        // Sorriso
        $joyLikelihood = $face->getJoyLikelihood();
        
        // Mensagens
        if ($joyLikelihood == \Google\Cloud\Vision\V1\Likelihood::VERY_LIKELY || $joyLikelihood == \Google\Cloud\Vision\V1\Likelihood::LIKELY) {
            $message = "A pessoa está sorrindo!";
        } else {
            $message = "A pessoa não está sorrindo.";
        }

        // Outros sentimentos, como raiva, surpresa, tristeza, etc.
        $angerLikelihood = $face->getAngerLikelihood();
        if ($angerLikelihood == \Google\Cloud\Vision\V1\Likelihood::VERY_LIKELY || $angerLikelihood == \Google\Cloud\Vision\V1\Likelihood::LIKELY) {
            $message .= "<br>A pessoa parece com raiva.";
        }

        $surpriseLikelihood = $face->getSurpriseLikelihood();
        if ($surpriseLikelihood == \Google\Cloud\Vision\V1\Likelihood::VERY_LIKELY || $surpriseLikelihood == \Google\Cloud\Vision\V1\Likelihood::LIKELY) {
            $message .= "<br>A pessoa parece surpresa.";
        }

        $sorrowLikelihood = $face->getSorrowLikelihood();
        if ($sorrowLikelihood == \Google\Cloud\Vision\V1\Likelihood::VERY_LIKELY || $sorrowLikelihood == \Google\Cloud\Vision\V1\Likelihood::LIKELY) {
            $message .= " A pessoa parece triste.";
        }

        echo json_encode(["success" => true, "message" => $message]);
    } else {
        echo json_encode(["success" => false, "message" => "Nenhum rosto detectado na imagem."]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Por favor, envie uma imagem válida."]);
}
?>
