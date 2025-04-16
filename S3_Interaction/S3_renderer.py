import boto3
from botocore.exceptions import NoCredentialsError, ClientError

# Initialize S3 client (boto3 will use environment variables for credentials)


access_key_id = input("Input your Access Key ID: ")
secret_access_key = input("Input your Secret Access Key: ")

session = boto3.Session(
    aws_access_key_id=access_key_id,
    aws_secret_access_key=secret_access_key,
    region_name='us-east-1'
)

s3 = session.client('s3')

bucket_name = 'cse-427-bucket'

def upload_file_to_s3(file_name, object_name=None):
    if object_name is None:
        object_name = file_name
    try:
        s3.upload_file(file_name, bucket_name, object_name)
        print(f"Uploaded {file_name} to {bucket_name}/{object_name}")
    except FileNotFoundError:
        print("The file was not found")
    except NoCredentialsError:
        print("Credentials not available")
    except ClientError as e:
        print(f"Error: {e}")

def list_s3_objects():
    try:
        response = s3.list_objects_v2(Bucket=bucket_name)
        if 'Contents' in response:
            print(f"Objects in {bucket_name}:")
            for obj in response['Contents']:
                print(f"- {obj['Key']}")
        else:
            print(f"No objects found in {bucket_name}.")
    except ClientError as e:
        print(f"Error: {e}")


while True:
    input = input("Type Input to see input, type List to list files")
    if input == "Input":
        fileName = input("type the name of the file you want to upload. make sure it is in the local S3_interaction folder.")
        upload_file_to_s3(fileName)
    elif input == "List":
        list_s3_objects()
    else:
        print("command not identified or exit typed. make sure the first letter is uppercase and spelled correctly.")
        print("exiting")
        break