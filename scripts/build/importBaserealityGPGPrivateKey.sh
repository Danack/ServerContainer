cd /home/github/BastionRPM
gpg --import basereality-GPG-KEY.private

# These are to allow us to build signed rpms
sudo echo "%_signature gpg" > /root/.rpmmacros
sudo echo "" >> /root/.rpmmacros
sudo echo "%_gpg_name Dan Ackroyd" >> /root/.rpmmacros

# sudo rpm --import ../basereality-GPG-KEY.private