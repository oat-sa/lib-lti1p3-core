pipeline {
    agent {
        label 'master'
    }
    stages {
        stage('Tests') {
            agent {
                docker {
                    image 'alexwijn/docker-git-php-composer'
                    reuseNode true
                }
            }
            environment {
                HOME = '.'
            }
            options {
                skipDefaultCheckout()
            }
            steps {
                withCredentials([string(credentialsId: 'jenkins_github_token', variable: 'GIT_TOKEN')]) {
                    sh(
                        label: 'Install/Update sources from Composer',
                        script: "COMPOSER_AUTH='{\"github-oauth\": {\"github.com\": \"$GIT_TOKEN\"}}\' composer update --no-interaction --no-ansi --no-progress"
                    )
                }
                sh(
                    label: 'Run backend tests',
                    script: './vendor/bin/phpunit'
                )
            }
        }
    }
}
