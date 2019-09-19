<?
include $_SERVER['DOCUMENT_ROOT']."/adm/common.php";

$response = array();
try{



  switch(strtolower($_SERVER['REQUEST_METHOD'])){
    case "get":
      $response['message'] = "게시글이 삭제되었습니다";
      break;
    case "put":
        $response['message'] = "게시글이 삭제되었습니다";
      break;
    case "post":
        // 설정
        $uploads_dir = $_SERVER['DOCUMENT_ROOT']."/adm/data/basket";
        $allowed_ext = array('jpg','jpeg','png','gif');

        // 변수 정리
        $error = $_FILES['myfile']['error'];
        $name = $_FILES['myfile']['name'];
        $ext = array_pop(explode('.', $name));

        // 오류 확인
        if( $error != UPLOAD_ERR_OK ) {
        switch( $error ) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $response['message'] = "파일이 너무 큽니다. ($error)";
                break;
            case UPLOAD_ERR_NO_FILE:
                $response['message'] = "파일이 첨부되지 않았습니다. ($error)";
                break;
            default:
                $response['message'] = "파일이 제대로 업로드되지 않았습니다. ($error)";
        }
        exit;
        }

        // 확장자 확인
        if( !in_array($ext, $allowed_ext) ) {
            $response['message'] = "허용되지 않는 확장자입니다.";
            exit;
        }

        // 파일 이동
        move_uploaded_file( $_FILES['myfile']['tmp_name'], "$uploads_dir/$idx.$ext");

        $response['message'] = "파일 업로드가 완료되었습니다.";
      break;
    case "delete":

      break;
  }
}
catch(Exception $e){
  $response['error'] = $e->getMessage();
}
echo json_encode($response);
?>
